<!--begin::Modal Selesaikan Pertandingan-->
<div class="modal fade" id="modal-finish-match" tabindex="-1" aria-labelledby="modal-finish-match-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="#" method="POST" id="form-finish-match">
                @csrf
                <input type="hidden" name="matchup_id" value="{{ old('matchup_id') }}" />
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-finish-match-label">Selesaikan Pertandingan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="text-body-secondary small mb-3" data-finish-info>Partai</p>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="finish-score-a" class="form-label">Skor Atlet A</label>
                            <input type="number" min="0" max="999" step="1" name="score_a" id="finish-score-a"
                                class="form-control @error('score_a') is-invalid @enderror"
                                value="{{ old('score_a') }}" required />
                            <div class="form-text" data-finish-name-a>Atlet A</div>
                            @error('score_a')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-6 mb-3">
                            <label for="finish-score-b" class="form-label">Skor Atlet B</label>
                            <input type="number" min="0" max="999" step="1" name="score_b" id="finish-score-b"
                                class="form-control @error('score_b') is-invalid @enderror"
                                value="{{ old('score_b') }}" required />
                            <div class="form-text" data-finish-name-b>Atlet B</div>
                            @error('score_b')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Pemenang</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="winner_id" id="finish-winner-a"
                                    value="" />
                                <label class="form-check-label" for="finish-winner-a" data-finish-label-a>Atlet A</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="winner_id" id="finish-winner-b"
                                    value="" />
                                <label class="form-check-label" for="finish-winner-b" data-finish-label-b>Atlet B</label>
                            </div>
                        </div>
                        <div class="invalid-feedback d-block">@error('winner_id'){{ $message }}@enderror</div>
                        <div class="form-text">Skor pemenang tidak boleh lebih rendah dari skor lawan.</div>
                    </div>

                    <div class="alert alert-info small mt-3 mb-0 d-none" data-finish-bracket>
                        Pemenang akan otomatis lanjut ke babak berikutnya pada bracket.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i>Simpan Hasil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!--end::Modal Selesaikan Pertandingan-->

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modal-finish-match');
            if (!modal) return;

            const form = modal.querySelector('form');
            const info = modal.querySelector('[data-finish-info]');
            const nameA = modal.querySelector('[data-finish-name-a]');
            const nameB = modal.querySelector('[data-finish-name-b]');
            const labelA = modal.querySelector('[data-finish-label-a]');
            const labelB = modal.querySelector('[data-finish-label-b]');
            const radioA = modal.querySelector('#finish-winner-a');
            const radioB = modal.querySelector('#finish-winner-b');
            const bracketNote = modal.querySelector('[data-finish-bracket]');
            const scoreA = modal.querySelector('[name="score_a"]');
            const scoreB = modal.querySelector('[name="score_b"]');

            const fill = function(trigger) {
                form.action = trigger.dataset.url;
                info.textContent = trigger.dataset.info || '';
                nameA.textContent = trigger.dataset.nameA || 'Atlet A';
                nameB.textContent = trigger.dataset.nameB || 'Atlet B';
                labelA.textContent = trigger.dataset.nameA || 'Atlet A';
                labelB.textContent = trigger.dataset.nameB || 'Atlet B';
                radioA.value = trigger.dataset.athleteA || '';
                radioB.value = trigger.dataset.athleteB || '';
                radioA.checked = radioB.checked = false;
                scoreA.value = trigger.dataset.scoreA || '';
                scoreB.value = trigger.dataset.scoreB || '';
                bracketNote.classList.toggle('d-none', trigger.dataset.bracket !== '1');
                modal.querySelector('[name="matchup_id"]').value = trigger.dataset.matchupId || '';
            };

            modal.addEventListener('show.bs.modal', function(event) {
                const trigger = event.relatedTarget;
                if (trigger) fill(trigger);
            });

            form.addEventListener('submit', function(event) {
                event.preventDefault();

                const winner = form.querySelector('[name="winner_id"]:checked');

                if (!winner) {
                    swalError('Pemenang belum dipilih', 'Silakan pilih pemenang pertandingan.');
                    return;
                }

                swalConfirm({
                    title: 'Selesaikan pertandingan?',
                    text: 'Skor, pemenang, dan status partai akan disimpan.'
                }).then(function(result) {
                    if (result.isConfirmed) HTMLFormElement.prototype.submit.call(form);
                });
            });

            const retryId = @json((string) old('matchup_id'));
            const retry = {
                score_a: @json(old('score_a')),
                score_b: @json(old('score_b')),
                winner_id: @json(old('winner_id'))
            };

            if (retryId && (retry.score_a !== null || retry.score_b !== null || retry.winner_id !== null)) {
                const trigger = document.querySelector('[data-finish-matchup="' + retryId + '"]');
                if (trigger) {
                    fill(trigger);
                    scoreA.value = retry.score_a || '';
                    scoreB.value = retry.score_b || '';
                    const winnerRadio = form.querySelector('[name="winner_id"][value="' + retry.winner_id + '"]');
                    if (winnerRadio) winnerRadio.checked = true;
                    new bootstrap.Modal(modal).show();
                }
            }
        });
    </script>
@endpush
