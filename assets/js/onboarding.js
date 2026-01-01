jQuery(document).ready(function ($) {



    let selectedLanguages = {
        en: 'English'
    };

    let translationLanguages = {};

    renderDefaultLanguages();
    renderTranslationLanguages();

    $(document).on('click', '.language-option', function (e) {
        e.preventDefault();

        let code = $(this).data('code');
        let name = $(this).data('name');

        if (!selectedLanguages[code]) {
            selectedLanguages = {}; 
            selectedLanguages[code] = name;
            renderDefaultLanguages();
            syncTranslationLanguages();
        }
    });

    $(document).on('keyup', '.language-search', function () {
        let value = $(this).val().toLowerCase();
        $('.language-option').each(function () {
            $(this).toggle(
                $(this).text().toLowerCase().indexOf(value) !== -1
            );
        });
    });

    function renderDefaultLanguages() {
        let labels = [];
        let hiddenInputs = '';

        $('.language-option').removeClass('active');

        $.each(selectedLanguages, function (code, name) {
            labels.push(name);
            hiddenInputs +=
                '<input type="hidden" name="languages[]" value="' + code + '">';
            $('.language-option[data-code="' + code + '"]').addClass('active');
        });

        $('#languagesDropdownBtn').text(labels.join(', '));
        $('.selected-languages').html(hiddenInputs);
    }


    $(document).on('click', '.translation-language-option', function (e) {
        e.preventDefault();

        let code = $(this).data('code');
        let name = $(this).data('name');

        if (!translationLanguages[code]) {
            translationLanguages[code] = name;
            renderTranslationLanguages();
        }
    });

    $(document).on('keyup', '.translation-language-search', function () {
        let value = $(this).val().toLowerCase();

        $('.translation-language-option').each(function () {
            $(this).toggle(
                $(this).text().toLowerCase().indexOf(value) !== -1
            );
        });
    });

    jQuery(document).ready(function($) {
    $('.translation-card').on('click', function() {
        $('.translation-card').removeClass('selected');
                $(this).addClass('selected');
        
        $(this).find('input[type="radio"]').prop('checked', true);
    });
    
    $('.translation-card input[type="radio"]').on('click', function(e) {
        e.stopPropagation();
        $('.translation-card').removeClass('selected');
        $(this).closest('.translation-card').addClass('selected');
    });
});
    function renderTranslationLanguages() {
        let labels = [];
        let hiddenInputs = '';

        $('.translation-language-option').removeClass('active');

        $.each(translationLanguages, function (code, name) {
            labels.push(name);
            hiddenInputs +=
                '<input type="hidden" name="translation_languages[]" value="' + code + '">';
            $('.translation-language-option[data-code="' + code + '"]').addClass('active');
        });

        if (labels.length) {
            $('#translationLanguagesDropdownBtn').text(labels.join(', '));
        } else {
            $('#translationLanguagesDropdownBtn').text('Select translation languages');
        }

        $('.selected-translation-languages').html(hiddenInputs);
    }

    function syncTranslationLanguages() {
        $('.translation-language-option').show();

        $.each(selectedLanguages, function (code) {
            $('.translation-language-option[data-code="' + code + '"]').hide();
            delete translationLanguages[code];
        });

        renderTranslationLanguages();
    }

    syncTranslationLanguages();


    $('.next-step').on('click', function (e) {
        e.preventDefault();
        navigateToStep($(this).data('next'));
    });

    $('.prev-step').on('click', function (e) {
        e.preventDefault();
        navigateToStep($(this).data('prev'));
    });

    $('.choose-mode').on('click', function () {
        $('.choose-mode').removeClass('active');
        $(this).addClass('active');
    });

    function navigateToStep(step) {
        $('.step-content').removeClass('active');
        $('.step-' + step).addClass('active');

        let stepIndex = getStepIndex(step);
        updateProgressBar(stepIndex);
    }

    function getStepIndex(step) {
        return [
            'languages',
            'url-format',
            'register-multilang',
            'translation-mode',
            'support',
            'plugins',
            'finished'
        ].indexOf(step);
    }

    function updateProgressBar(currentIndex) {
        $('.step-number').removeClass('active completed');

        $('.step-number').each(function (index) {
            if (index < currentIndex) {
                $(this).addClass('completed');
            } else if (index === currentIndex) {
                $(this).addClass('active');
            }
        });
    }

});
