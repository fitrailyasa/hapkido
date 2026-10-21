; Inno Setup 6 script — Hapkido Championship
;
; Digunakan oleh package.ps1 (ISCC.exe). Bisa juga dijalankan manual:
;   "C:\Program Files (x86)\Inno Setup 6\ISCC.exe" tools\build-exe\installer.iss
;
; Sumber file: tools\build-exe\dist\app\
; Output:     tools\build-exe\dist\Setup-Hapkido.exe

#define MyAppName "Hapkido Championship"
#define MyAppVersion "2026.1"
#define MyAppPublisher "Hapkido"
#define MyAppExeName "Hapkido.exe"
#define MyAppURL "http://127.0.0.1:8000"

[Setup]
AppId={{A7C3E9B1-4D2F-4F8A-9B1E-6C2D8E0F1A3B}}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
DefaultDirName={localappdata}\Programs\Hapkido
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
; Install ke LocalAppData agar SQLite/storage bisa ditulis tanpa Administrator.
PrivilegesRequired=lowest
OutputDir=dist
OutputBaseFilename=Setup-Hapkido
Compression=lzma2
SolidCompression=yes
WizardStyle=modern
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
UninstallDisplayIcon={app}\{#MyAppExeName}
SetupLogging=yes
; Jangan hapus database user saat upgrade (file tidak ikut di paket).
CloseApplications=force
RestartApplications=no

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "Buat ikon di Desktop"; GroupDescription: "Ikon tambahan:"

[Files]
; Seluruh staging app (PHP runtime + Laravel + launcher).
Source: "dist\app\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"
Name: "{group}\Uninstall {#MyAppName}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: desktopicon

[Run]
Filename: "{app}\{#MyAppExeName}"; Description: "Jalankan {#MyAppName}"; Flags: nowait postinstall skipifsilent

[UninstallDelete]
; Hapus artefak runtime yang dibuat saat jalan (bukan source paket).
Type: files; Name: "{app}\.env"
Type: filesandordirs; Name: "{app}\bootstrap\cache\*"
Type: filesandordirs; Name: "{app}\storage\framework\cache\*"
Type: filesandordirs; Name: "{app}\storage\framework\sessions\*"
Type: filesandordirs; Name: "{app}\storage\framework\views\*"
Type: filesandordirs; Name: "{app}\storage\logs\*"
Type: filesandordirs; Name: "{app}\runtime\php\logs\*"
; Database & upload user: biarkan, atau hapus manual. Uncomment untuk hapus total:
; Type: files; Name: "{app}\database\database.sqlite"
; Type: filesandordirs; Name: "{app}\storage\app\public\*"

[Code]
function InitializeSetup(): Boolean;
begin
  Result := True;
  if not IsWin64 then
  begin
    MsgBox('Installer ini membutuhkan Windows 64-bit.', mbError, MB_OK);
    Result := False;
  end;
end;
