using System;
using System.Diagnostics;
using System.IO;
using System.Threading;

namespace Hapkido
{
    /// <summary>
    /// Small launcher for the packaged Laravel application.
    ///
    /// It prepares the application on the first run (.env, application key,
    /// SQLite database, migrations, storage link), starts "php artisan serve"
    /// and opens the browser. Closing the window stops the server.
    /// </summary>
    internal static class Launcher
    {
        private static Process _server;

        private static int Main(string[] args)
        {
            string root = AppDomain.CurrentDomain.BaseDirectory.TrimEnd('\\');
            string phpDir = Path.Combine(root, "runtime", "php");
            string php = Path.Combine(phpDir, "php.exe");
            string envFile = Path.Combine(root, ".env");
            string envTemplate = Path.Combine(root, "env.production");
            string configFile = Path.Combine(root, "server.txt");

            Console.Title = "Hapkido Championship";

            try
            {
                if (!File.Exists(php))
                {
                    return Fail("Runtime PHP tidak ditemukan: " + php);
                }

                string host = "127.0.0.1";
                string port = "8000";

                ReadConfig(configFile, ref host, ref port);

                if (args.Length > 0 && args[0].Length > 0) host = args[0];
                if (args.Length > 1 && args[1].Length > 0) port = args[1];

                // The .env is created on the first run, so every installation gets
                // its own application key. Older builds used ';' comments which
                // Laravel dotenv rejects — rewrite those from the template.
                if (!File.Exists(envFile) && File.Exists(envTemplate))
                {
                    File.Copy(envTemplate, envFile);
                }
                else if (File.Exists(envFile) && File.Exists(envTemplate) && EnvLooksBroken(envFile))
                {
                    string previousKey = ReadEnv(envFile, "APP_KEY");
                    File.Copy(envTemplate, envFile, true);
                    if (previousKey.Length > 0)
                    {
                        WriteEnv(envFile, "APP_KEY", previousKey);
                    }
                }

                if (!File.Exists(envFile))
                {
                    return Fail("File .env tidak ditemukan di " + root);
                }

                string url = "http://" + (host == "0.0.0.0" ? "127.0.0.1" : host) + ":" + port;
                WriteEnv(envFile, "APP_URL", url);

                // SQLite database file (absolute path — empty DB_DATABASE breaks PDO on Windows).
                string database = Path.Combine(root, "database", "database.sqlite");
                Directory.CreateDirectory(Path.GetDirectoryName(database));
                WriteEnv(envFile, "DB_DATABASE", database.Replace('\\', '/'));

                bool firstRun = !File.Exists(database);

                if (firstRun)
                {
                    File.WriteAllText(database, string.Empty);
                }

                Console.WriteLine("Menyiapkan aplikasi ...");
                EnsureStorageDirs(root);

                if (ReadEnv(envFile, "APP_KEY").Length == 0)
                {
                    Run(php, root, phpDir, "artisan key:generate --force");
                }

                // public/storage must point at storage/app/public. A junction does
                // not need administrator rights on Windows.
                string link = Path.Combine(root, "public", "storage");
                string target = Path.Combine(root, "storage", "app", "public");

                if (!Directory.Exists(link) && !File.Exists(link))
                {
                    Directory.CreateDirectory(target);
                    Run("cmd.exe", root, null, "/c mklink /J \"" + link + "\" \"" + target + "\"");
                }

                // Migrations are idempotent, so they run on every start and pick up
                // updates of a newer installer.
                Run(php, root, phpDir, "artisan migrate --force");

                if (firstRun)
                {
                    Run(php, root, phpDir, "artisan db:seed --class=ProductionSeeder --force");
                }

                Console.WriteLine();
                Console.WriteLine("Aplikasi berjalan di " + url);
                Console.WriteLine("Browser akan dibuka otomatis ...");
                Console.WriteLine("Tekan [ESC] atau tutup jendela ini untuk berhenti.");
                Console.WriteLine();

                _server = Start(php, root, phpDir, "artisan serve --host=" + host + " --port=" + port);

                if (_server == null || _server.HasExited)
                {
                    return Fail("Server gagal dijalankan. Periksa file .env dan database.");
                }

                Thread.Sleep(2500);
                OpenBrowser(url);

                while (true)
                {
                    if (_server.HasExited)
                    {
                        break;
                    }

                    if (Console.KeyAvailable && Console.ReadKey(true).Key == ConsoleKey.Escape)
                    {
                        break;
                    }

                    Thread.Sleep(200);
                }

                Stop();

                return 0;
            }
            catch (Exception exception)
            {
                Stop();

                return Fail(exception.Message);
            }
        }

        private static void EnsureStorageDirs(string root)
        {
            string[] dirs =
            {
                Path.Combine(root, "storage", "app", "public"),
                Path.Combine(root, "storage", "framework", "cache", "data"),
                Path.Combine(root, "storage", "framework", "sessions"),
                Path.Combine(root, "storage", "framework", "views"),
                Path.Combine(root, "storage", "logs"),
                Path.Combine(root, "bootstrap", "cache"),
            };

            foreach (string dir in dirs)
            {
                Directory.CreateDirectory(dir);
            }
        }

        private static Process Start(string file, string workingDirectory, string phpDir, string arguments, bool redirect = false)
        {
            try
            {
                Process process = new Process();
                process.StartInfo = new ProcessStartInfo(file, arguments)
                {
                    WorkingDirectory = workingDirectory,
                    UseShellExecute = false,
                    CreateNoWindow = true,
                    RedirectStandardOutput = redirect,
                    RedirectStandardError = redirect
                };

                if (!string.IsNullOrEmpty(phpDir))
                {
                    process.StartInfo.EnvironmentVariables["PHPRC"] = phpDir;
                }

                process.Start();

                return process;
            }
            catch (Exception exception)
            {
                Console.WriteLine("Gagal menjalankan " + file + ": " + exception.Message);

                return null;
            }
        }

        private static void Run(string file, string workingDirectory, string phpDir, string arguments)
        {
            Process process = Start(file, workingDirectory, phpDir, arguments, redirect: true);

            if (process == null)
            {
                throw new InvalidOperationException("Perintah gagal: " + file + " " + arguments);
            }

            string stdout = process.StandardOutput.ReadToEnd();
            string stderr = process.StandardError.ReadToEnd();
            process.WaitForExit();

            if (!string.IsNullOrWhiteSpace(stdout))
            {
                Console.WriteLine(stdout.TrimEnd());
            }

            if (process.ExitCode != 0)
            {
                string detail = string.IsNullOrWhiteSpace(stderr) ? stdout : stderr;
                detail = (detail ?? string.Empty).Trim();

                if (detail.Length > 800)
                {
                    detail = detail.Substring(detail.Length - 800);
                }

                throw new InvalidOperationException(
                    "Perintah gagal (" + process.ExitCode + "): " + file + " " + arguments
                    + (detail.Length > 0 ? Environment.NewLine + detail : string.Empty));
            }
        }

        private static void Stop()
        {
            if (_server == null || _server.HasExited)
            {
                return;
            }

            try
            {
                _server.Kill();
                _server.WaitForExit(5000);
            }
            catch
            {
                // The process is already gone.
            }
        }

        private static void OpenBrowser(string url)
        {
            try
            {
                Process.Start(new ProcessStartInfo(url) { UseShellExecute = true });
            }
            catch
            {
                // The browser is optional; the address is printed on screen.
            }
        }

        private static bool EnvLooksBroken(string file)
        {
            foreach (string line in File.ReadAllLines(file))
            {
                string trimmed = line.TrimStart();

                if (trimmed.StartsWith(";") && !trimmed.StartsWith("#!"))
                {
                    return true;
                }
            }

            return false;
        }

        private static string ReadEnv(string file, string key)
        {
            foreach (string line in File.ReadAllLines(file))
            {
                string trimmed = line.Trim();

                if (trimmed.StartsWith(key + "="))
                {
                    return trimmed.Substring(key.Length + 1).Trim().Trim('"');
                }
            }

            return string.Empty;
        }

        private static void WriteEnv(string file, string key, string value)
        {
            string[] lines = File.ReadAllLines(file);
            bool found = false;

            for (int i = 0; i < lines.Length; i++)
            {
                string trimmed = lines[i].TrimStart();

                if (trimmed.StartsWith(key + "="))
                {
                    lines[i] = key + "=" + value;
                    found = true;
                    break;
                }
            }

            if (!found)
            {
                Array.Resize(ref lines, lines.Length + 1);
                lines[lines.Length - 1] = key + "=" + value;
            }

            File.WriteAllLines(file, lines);
        }

        private static void ReadConfig(string file, ref string host, ref string port)
        {
            if (!File.Exists(file))
            {
                return;
            }

            foreach (string line in File.ReadAllLines(file))
            {
                string trimmed = line.Trim();

                if (trimmed.StartsWith("host="))
                {
                    host = trimmed.Substring(5).Trim();
                }
                else if (trimmed.StartsWith("port="))
                {
                    port = trimmed.Substring(5).Trim();
                }
            }
        }

        private static int Fail(string message)
        {
            Console.WriteLine();
            Console.WriteLine("TERJADI KESALAHAN");
            Console.WriteLine(message);
            Console.WriteLine();
            Console.WriteLine("Tekan [ENTER] untuk menutup ...");
            Console.ReadLine();

            return 1;
        }
    }
}
