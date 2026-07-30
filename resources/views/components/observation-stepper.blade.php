@php
    $weatherFields = ['kelembaban_tanah', 'kondisi_drainase', 'metode_data_hujan', 'curah_hujan_kategori', 'curah_hujan_mm_bulanan', 'periode_curah_hujan', 'sumber_curah_hujan', 'musim_saat_ini', 'catatan_observasi'];
    $conditionFields = ['warna_daun', 'foto_observasi'];
    $initialObservationStep = collect($weatherFields)->contains(fn ($field) => $errors->has($field))
        ? 3
        : (collect($conditionFields)->contains(fn ($field) => $errors->has($field)) ? 2 : 1);
@endphp

<div id="observation-stepper" data-initial-step="{{ $initialObservationStep }}" class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="grid grid-cols-3 gap-2" aria-label="Tahapan observasi">
        @foreach([
            1 => ['Blok dan Tanggal', 'Pilih lahan'],
            2 => ['Kondisi Tanaman', 'Pemeriksaan daun'],
            3 => ['Kesiapan Pupuk', 'Tanah dan cuaca'],
        ] as $step => [$label, $description])
            <button type="button" data-step-target="{{ $step }}"
                class="observation-step-button flex min-w-0 items-center gap-2 rounded-xl border px-2.5 py-2 text-left transition-colors {{ $step === $initialObservationStep ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400' }}"
                aria-current="{{ $step === $initialObservationStep ? 'step' : 'false' }}">
                <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-current/10 text-xs font-bold">{{ $step }}</span>
                <span class="min-w-0">
                    <span class="block truncate text-[11px] font-semibold sm:text-xs">{{ $label }}</span>
                    <span class="hidden truncate text-[10px] opacity-75 sm:block">{{ $description }}</span>
                </span>
            </button>
        @endforeach
    </div>
</div>

@once
    @push('scripts')
        <script>
        (function () {
            var stepper = document.getElementById('observation-stepper');
            if (!stepper) return;

            var currentStep = Number(stepper.dataset.initialStep || 1);
            var stepButtons = Array.from(stepper.querySelectorAll('[data-step-target]'));
            var groups = Array.from(document.querySelectorAll('[data-observation-step]'));
            var blockGroup = document.querySelector('[data-observation-step="1"]');
            var phaseBanner = document.getElementById('banner-tbm');
            if (blockGroup && phaseBanner) blockGroup.appendChild(phaseBanner);
            var previousButton = document.getElementById('observation-step-previous');
            var nextButton = document.getElementById('observation-step-next');
            var cancelLink = document.getElementById('observation-step-cancel');
            var navigation = document.getElementById('observation-step-navigation');

            function validateFirstStep() {
                var dateInput = document.querySelector('[name="tanggal_observasi"]');
                var blockInput = document.querySelector('[name="blok_lahan_id"]');

                if (dateInput && !dateInput.checkValidity()) {
                    dateInput.reportValidity();
                    return false;
                }

                if (!blockInput || !blockInput.value) {
                    if (typeof showToast === 'function') {
                        showToast('warning', 'Pilih blok lahan sebelum melanjutkan.', 4000);
                    }
                    return false;
                }

                return true;
            }

            function validateConditionStep() {
                var leafInput = document.querySelector('[name="warna_daun"]');
                if (leafInput && !leafInput.checkValidity()) {
                    leafInput.reportValidity();
                    return false;
                }
                return true;
            }

            function setStep(step, shouldScroll) {
                currentStep = Math.max(1, Math.min(3, Number(step)));

                groups.forEach(function (group) {
                    group.classList.toggle('hidden', Number(group.dataset.observationStep) !== currentStep);
                });

                stepButtons.forEach(function (button) {
                    var active = Number(button.dataset.stepTarget) === currentStep;
                    button.classList.toggle('border-emerald-600', active);
                    button.classList.toggle('bg-emerald-600', active);
                    button.classList.toggle('text-white', active);
                    button.classList.toggle('border-slate-200', !active);
                    button.classList.toggle('bg-white', !active);
                    button.classList.toggle('text-slate-500', !active);
                    button.classList.toggle('dark:border-slate-600', !active);
                    button.classList.toggle('dark:bg-slate-800', !active);
                    button.classList.toggle('dark:text-slate-400', !active);
                    button.setAttribute('aria-current', active ? 'step' : 'false');
                });

                if (previousButton) previousButton.classList.toggle('hidden', currentStep === 1);
                if (nextButton) nextButton.classList.toggle('hidden', currentStep === 3);
                if (cancelLink) cancelLink.classList.toggle('hidden', currentStep === 3);
                if (navigation) navigation.classList.toggle('justify-between', currentStep > 1);

                if (shouldScroll) {
                    stepper.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            stepButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var target = Number(this.dataset.stepTarget);
                    if (target > currentStep && currentStep === 1 && !validateFirstStep()) return;
                    if (target > currentStep + 1) {
                        setStep(currentStep + 1, true);
                        return;
                    }
                    if (target > currentStep && currentStep === 2 && !validateConditionStep()) return;
                    setStep(target, true);
                });
            });

            if (previousButton) {
                previousButton.addEventListener('click', function () {
                    setStep(currentStep - 1, true);
                });
            }

            if (nextButton) {
                nextButton.addEventListener('click', function () {
                    if (currentStep === 1 && !validateFirstStep()) return;
                    if (currentStep === 2 && !validateConditionStep()) return;
                    setStep(currentStep + 1, true);
                });
            }

            setStep(currentStep, false);
        })();
        </script>
    @endpush
@endonce