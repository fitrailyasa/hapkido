@if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            swalSuccess('Berhasil', @json(session('success')));
        });
    </script>
@endif

@if (session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            swalError('Gagal', @json(session('error')));
        });
    </script>
@endif

@if (session('info'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            swalInfo('Informasi', @json(session('info')));
        });
    </script>
@endif

@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            swalError('Validasi gagal', @json(implode(' • ', $errors->all())));
        });
    </script>
@endif
