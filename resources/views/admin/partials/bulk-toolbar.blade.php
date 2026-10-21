{{-- Shared bulk delete toolbar of an admin table.

     The table itself adds a [data-bulk-all] checkbox in the header and a
     [data-bulk-row] checkbox per row; the JS in the admin layout collects the
     selected ids and posts them to the given bulk destroy route after a
     confirmation. --}}
@can($permission)
    <form action="{{ route($route) }}" method="POST" data-bulk-form class="mb-3">
        @csrf
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="text-body-secondary small">
                <i class="bi bi-check2-square me-1"></i>
                <span data-bulk-count>0</span> data dipilih
            </span>
            <button type="submit" class="btn btn-sm btn-danger" data-bulk-submit disabled>
                <i class="bi bi-trash me-1"></i>Hapus Terpilih
            </button>
        </div>
    </form>
@endcan
