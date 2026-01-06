jQuery(document).ready(function ($) {
  const STORAGE_KEY = "aimt_onboarding_state_v1";

  let state = {
    step: "languages",
    selectedLanguages: { en: "English" },
    translationLanguages: {},
    postType: "",
    urlFormat: "subdirectory",
    wpmlKey: "",
    translationMode: "",
    support: {
      support_docs: true,
      support_forum: false,
      support_email: false,
    },
    plugins: {
      plugin_woocommerce: true,
      plugin_seo: true,
      plugin_slug: false,
      plugin_media: false,
    },
  };

  function summaryHtmlForState(s) {
    const dl = [];
    dl.push(
      `<strong>Default:</strong> ${
        Object.values(s.selectedLanguages || {}).join(", ") || "—"
      }`
    );
    dl.push(
      `<strong>Translation:</strong> ${
        Object.values(s.translationLanguages || {}).join(", ") || "—"
      }`
    );
    dl.push(`<strong>Post Type:</strong> ${s.postType || "—"}`);
    dl.push(`<strong>URL Format:</strong> ${s.urlFormat || "—"}`);
    dl.push(`<strong>Mode:</strong> ${s.translationMode || "—"}`);
    dl.push(`<strong>MLI Key:</strong> ${s.wpmlKey ? "Provided" : "—"}`);
    return `<div style="margin-top:8px;">${dl
      .map((i) => `<div>${i}</div>`)
      .join("")}</div>`;
  }

  function showStoredAlert() {
    if ($(".aimt-stored-alert").length) return;

    const alertHtml = `
      <div class="aimt-stored-alert alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom:20px;">
        <div>
          <strong>All data saved locally.</strong>
          Your onboarding choices are stored in localStorage.
        </div>
        ${summaryHtmlForState(state)}
        <div style="margin-top:8px;">
          <a href="#" class="aimt-view-console">View details in console</a>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="outline:none;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>
    `;

    const $container = $(".aimt-onboarding .container").first();
    if ($container.length) {
      $container.prepend(alertHtml);
    } else {
      $(".aimt-onboarding").prepend(alertHtml);
    }

    $(document).on("click", ".aimt-view-console", function (e) {
      e.preventDefault();
      console.log("AIMT onboarding stored state:", state);
      alert("Stored data printed to console.");
    });
  }

  function hideStoredAlert() {
    $(".aimt-stored-alert").remove();
  }

  
  function isStateComplete() {
    const hasDefaultLang =
      Object.keys(state.selectedLanguages || {}).length > 0;
    const hasTranslationLangs =
      Object.keys(state.translationLanguages || {}).length > 0;
    const hasPostType = !!state.postType;
    const hasUrlFormat = !!state.urlFormat;
    const hasTranslationMode = !!state.translationMode;
    return (
      hasDefaultLang &&
      hasTranslationLangs &&
      hasPostType &&
      hasUrlFormat &&
      hasTranslationMode
    );
  }

  function saveState() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
      console.log("AIMT onboarding saved state:", state);
    } catch (e) {
      console.warn("Could not save onboarding state", e);
    }
    handleStoredAlertDisplay();
  }

  function loadState() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      const parsed = JSON.parse(raw);
      state = $.extend(true, {}, state, parsed);
      console.log("AIMT onboarding loaded state:", state);
    } catch (e) {
      console.warn("Could not load onboarding state", e);
    }
  }

  function clearState() {
    localStorage.removeItem(STORAGE_KEY);
    state = {
      step: "languages",
      selectedLanguages: { en: "English" },
      translationLanguages: {},
      postType: "",
      urlFormat: "subdirectory",
      wpmlKey: "",
      translationMode: "",
      support: {
        support_docs: true,
        support_forum: false,
        support_email: false,
      },
      plugins: {
        plugin_woocommerce: true,
        plugin_seo: true,
        plugin_slug: false,
        plugin_media: false,
      },
    };
    saveState();
  }

  function handleStoredAlertDisplay() {
    if (state.step === "finished" && isStateComplete()) {
      hideStoredAlert(); 
      showStoredAlert();
    } else {
      hideStoredAlert();
    }
  }

  function renderDefaultLanguages() {
    let labels = [];
    let hiddenInputs = "";

    $(".language-option").removeClass("active");

    $.each(state.selectedLanguages, function (code, name) {
      labels.push(name);
      hiddenInputs += `<input type="hidden" name="languages[]" value="${code}">`;
      $(`.language-option[data-code="${code}"]`).addClass("active");
    });

    $("#languagesDropdownBtn").text(
      labels.length ? labels.join(", ") : "Select default language"
    );
    $(".selected-languages").html(hiddenInputs);
  }

  function renderTranslationLanguages() {
    let labels = [];
    let hiddenInputs = "";

    $(".translation-language-option").removeClass("active");

    $.each(state.translationLanguages, function (code, name) {
      labels.push(name);
      hiddenInputs += `<input type="hidden" name="translation_languages[]" value="${code}">`;
      $(`.translation-language-option[data-code="${code}"]`).addClass("active");
    });

    $("#translationLanguagesDropdownBtn").text(
      labels.length ? labels.join(", ") : "Select translation languages"
    );
    $(".selected-translation-languages").html(hiddenInputs);
  }

  function syncTranslationLanguages() {
    $(".translation-language-option").show();

    $.each(state.selectedLanguages, function (code) {
      $(`.translation-language-option[data-code="${code}"]`).hide();
      if (state.translationLanguages[code]) {
        delete state.translationLanguages[code];
      }
    });

    renderTranslationLanguages();
  }

  
  loadState();

  
  renderDefaultLanguages();
  renderTranslationLanguages();
  syncTranslationLanguages();

  handleStoredAlertDisplay();

  function navigateToStep(step) {
    state.step = step || "languages";
    $(".step-content").removeClass("active");
    $(".step-" + state.step).addClass("active");
    updateProgressBar(getStepIndex(state.step));
    saveState();
  }

  function getStepIndex(step) {
    return [
      "languages",
      "url-format",
      "register-multilang",
      "translation-mode",
      "support",
      "plugins",
      "finished",
    ].indexOf(step);
  }

  function updateProgressBar(currentIndex) {
    $(".step-number").removeClass("active completed");
    $(".step-number").each(function (index) {
      if (index < currentIndex) {
        $(this).addClass("completed");
      } else if (index === currentIndex) {
        $(this).addClass("active");
      }
    });
  }

 
  navigateToStep(state.step);


  $(document).on("click", ".language-option", function (e) {
    e.preventDefault();
    const code = $(this).data("code");
    const name = $(this).data("name");
    state.selectedLanguages = {};
    state.selectedLanguages[code] = name;
    renderDefaultLanguages();
    syncTranslationLanguages();
    saveState();
  });

  $(document).on("keyup", ".language-search", function () {
    const value = $(this).val().toLowerCase();
    $(".language-option").each(function () {
      $(this).toggle($(this).text().toLowerCase().includes(value));
    });
  });

  $(document).on("click", ".translation-language-option", function (e) {
    e.preventDefault();
    const code = $(this).data("code");
    const name = $(this).data("name");
    if (!state.translationLanguages[code]) {
      state.translationLanguages[code] = name;
      renderTranslationLanguages();
      saveState();
    }
  });

  $(document).on("keyup", ".translation-language-search", function () {
    const value = $(this).val().toLowerCase();
    $(".translation-language-option").each(function () {
      $(this).toggle($(this).text().toLowerCase().includes(value));
    });
  });

  $(document).on("click", ".post-type-option", function (e) {
    e.preventDefault();
    e.stopPropagation();
    const postType = $(this).data("post-type");
    const label = $.trim($(this).text());
    state.postType = postType;
    $("#aimt_post_type").val(postType);
    $("#postTypeDropdownBtn").text(label);
    const $dropdown = $(this).closest(".dropdown");
    const $toggle = $dropdown.find(".dropdown-toggle");
    if ($toggle.length && typeof $toggle.dropdown === "function") {
      try {
        $toggle.dropdown("hide");
      } catch (err) {
        $dropdown.removeClass("show");
        $dropdown.find(".dropdown-menu").removeClass("show");
        $toggle.attr("aria-expanded", "false");
      }
    } else {
      $dropdown.removeClass("show");
      $dropdown.find(".dropdown-menu").removeClass("show");
      $toggle.attr("aria-expanded", "false");
    }
    saveState();
  });

  $(document).on("change", 'input[name="url_format"]', function () {
    state.urlFormat = $(this).val();
    saveState();
  });

  $(document).on("input", "#wpml_key", function () {
    state.wpmlKey = $(this).val();
    saveState();
  });

  $(document).on("click", ".choose-mode", function (e) {
    e.preventDefault();
    $(".choose-mode").removeClass("active");
    $(this).addClass("active");
    state.translationMode = $(this).data("mode");
    saveState();
  });

  $(document).on(
    "change",
    "#support_docs, #support_forum, #support_email",
    function () {
      const id = $(this).attr("id");
      state.support[id] = $(this).is(":checked");
      saveState();
    }
  );

  $(document).on(
    "change",
    "#plugin_woocommerce, #plugin_seo, #plugin_slug, #plugin_media",
    function () {
      const id = $(this).attr("id");
      state.plugins[id] = $(this).is(":checked");
      saveState();
    }
  );

  $(".next-step").on("click", function (e) {
    e.preventDefault();
    const next = $(this).data("next");
    navigateToStep(next);
  });

  $(".prev-step").on("click", function (e) {
    e.preventDefault();
    const prev = $(this).data("prev");
    navigateToStep(prev);
  });

  if (state.urlFormat) {
    $(`input[name="url_format"][value="${state.urlFormat}"]`).prop(
      "checked",
      true
    );
  }

  if (state.wpmlKey) {
    $("#wpml_key").val(state.wpmlKey);
  }

  if (state.translationMode) {
    $(".choose-mode").removeClass("active");
    $(`.choose-mode[data-mode="${state.translationMode}"]`).addClass("active");
  }

  Object.keys(state.support).forEach(function (k) {
    $(`#${k}`).prop("checked", !!state.support[k]);
  });

  Object.keys(state.plugins).forEach(function (k) {
    $(`#${k}`).prop("checked", !!state.plugins[k]);
  });

  updateProgressBar(getStepIndex(state.step));
});
