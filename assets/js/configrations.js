jQuery(document).ready(function ($) {
  console.log("AIMT Onboarding Script Loaded");
  console.log("AJAX URL:", ajaxurl);
  console.log("Nonce:", aimtOnboardingData.nonce);

  const aimtNonce = aimtOnboardingData.nonce;

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
      `<strong>Default:</strong> ${Object.values(s.selectedLanguages || {}).join(", ") || "—"
      }`
    );
    dl.push(
      `<strong>Translation:</strong> ${Object.values(s.translationLanguages || {}).join(", ") || "—"
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
          <strong>All data saved in database.</strong>
          Your onboarding choices are stored in WordPress options.
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

    const $container = $(".aimt-configrations .container").first();
    if ($container.length) {
      $container.prepend(alertHtml);
    } else {
      $(".aimt-configrations").prepend(alertHtml);
    }

    $(document).on("click", ".aimt-view-console", function (e) {
      e.preventDefault();
      console.log("AIMT onboarding database state:", state);
      // alert("Stored data printed to console.");
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
    console.log("Saving state to database:", state);

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "aimt_save_onboarding",
        nonce: aimtNonce,
        state: JSON.stringify(state),
      },
      success: function (response) {
        if (response.success) {
          console.log("✅ State saved successfully:", response.data);
          console.log("Response:", response);
          handleStoredAlertDisplay();

          setTimeout(function () {
            $.ajax({
              url: ajaxurl,
              type: "POST",
              data: {
                action: "aimt_load_onboarding",
                nonce: aimtNonce,
              },
              success: function (loadResponse) {
                if (loadResponse.success) {
                  console.log("✅ Load test successful:", loadResponse.data);
                }
              },
            });
          }, 500);
        } else {
          console.error("❌ Failed to save state:", response.data);
        }
      },
      error: function (xhr, status, error) {
        console.error("❌ AJAX error saving state:", error);
        console.error("Status:", status);
        console.error("XHR:", xhr);
      },
    });
  }

  function loadState(callback) {
    console.log("Loading state from database...");

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "aimt_load_onboarding",
        nonce: aimtNonce,
      },
      success: function (response) {
        console.log("Load response:", response);

        if (response.success && response.data.exists) {
          console.log("✅ State loaded from database:", response.data.state);
          state = $.extend(true, {}, state, response.data.state);

          // normalize to plain objects (avoid arrays with numeric keys)
          if (Array.isArray(state.selectedLanguages)) {
            var tmp = {};
            state.selectedLanguages.forEach(function (name, idx) {
              // try to preserve code if provided as {code: name} objects
              if (typeof name === "object") {
                var keys = Object.keys(name);
                tmp[keys[0]] = name[keys[0]];
              } else {
                // fallback: keep value as name with generated key
                tmp["lang_" + idx] = name;
              }
            });
            state.selectedLanguages = tmp;
          }
          if (
            !state.selectedLanguages ||
            typeof state.selectedLanguages !== "object"
          ) {
            state.selectedLanguages = {};
          }

          if (Array.isArray(state.translationLanguages)) {
            var tmp2 = {};
            state.translationLanguages.forEach(function (name, idx) {
              if (typeof name === "object") {
                var keys = Object.keys(name);
                tmp2[keys[0]] = name[keys[0]];
              } else {
                tmp2["lang_" + idx] = name;
              }
            });
            state.translationLanguages = tmp2;
          }
          if (
            !state.translationLanguages ||
            typeof state.translationLanguages !== "object"
          ) {
            state.translationLanguages = {};
          }

          renderDefaultLanguages();
          renderTranslationLanguages();
          syncTranslationLanguages();

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
            $(`.choose-mode[data-mode="${state.translationMode}"]`).addClass(
              "active"
            );
          }

          navigateToStep(state.step);

          if (callback) callback();
        } else {
          console.log("ℹ️ No saved state found in database");
          // ensure objects exist
          state.selectedLanguages = state.selectedLanguages || {};
          state.translationLanguages = state.translationLanguages || {};
          if (callback) callback();
        }
      },
      error: function (xhr, status, error) {
        console.error("❌ AJAX error loading state:", error);
        if (callback) callback();
      },
    });
  }

  function clearState() {
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "aimt_clear_configration",
        nonce: aimtNonce,
      },
      success: function (response) {
        if (response.success) {
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

          renderDefaultLanguages();
          renderTranslationLanguages();
          syncTranslationLanguages();
          navigateToStep("languages");
          hideStoredAlert();

          console.log("✅ State cleared from database");
        }
      },
      error: function (xhr, status, error) {
        console.error("❌ AJAX error clearing state:", error);
        console.error("Status:", status);
        console.error("XHR:", xhr);
      },
    });
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
    var labels = [];
    var hiddenInputs = "";

    $(".language-option").removeClass("active");

    // treat as object map
    Object.keys(state.selectedLanguages || {}).forEach(function (code) {
      var name = state.selectedLanguages[code];
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
    var labels = [];
    var hiddenInputs = "";

    $(".translation-language-option").removeClass("active");

    Object.keys(state.translationLanguages || {}).forEach(function (code) {
      var name = state.translationLanguages[code];
      labels.push(name);
      hiddenInputs += `<input type="hidden" name="translation_languages[]" value="${code}">`;
      $(`.translation-language-option[data-code="${code}"]`).addClass("active");
    });

    $("#translationLanguagesDropdownBtn").text(
      labels.length ? labels.join(", ") : "Select translation languages"
    );
    $(".selected-translation-languages").html(hiddenInputs);
  }

  // add helper to update the translation dropdown button label
  function updateTranslationDropdownLabel() {
    var labels = Object.keys(state.translationLanguages || {}).map(function (
      code
    ) {
      return state.translationLanguages[code];
    });
    $("#translationLanguagesDropdownBtn").text(
      labels.length ? labels.join(", ") : "Select translation languages"
    );
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

  loadState(function () {
    handleStoredAlertDisplay();
  });

  $(document).on("click", ".language-option", function (e) {
    e.preventDefault();
    e.stopPropagation();
    const $el = $(this);
    const code = $el.data("code");
    const name = $el.data("name") || $el.text().trim();
    console.log("language click:", code, name);

    if (!state.selectedLanguages || Array.isArray(state.selectedLanguages)) {
      state.selectedLanguages = {};
    }

    // toggle selection (allow multiple default languages)
    if (state.selectedLanguages[code]) {
      delete state.selectedLanguages[code];
    } else {
      state.selectedLanguages[code] = name;
    }

    renderDefaultLanguages();
    syncTranslationLanguages();
    updateTranslationDropdownLabel();
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
    e.stopPropagation();

    const code = $(this).data("code");
    const name = $(this).data("name") || $(this).text().trim();

    if (
      !state.translationLanguages ||
      Array.isArray(state.translationLanguages)
    ) {
      state.translationLanguages = {};
    }

    if (state.selectedLanguages && state.selectedLanguages[code]) {
      // optionally show a small feedback
      $(this).addClass("disabled");
      setTimeout(() => $(this).removeClass("disabled"), 600);
      return;
    }

    // toggle selection
    if (state.translationLanguages[code]) {
      delete state.translationLanguages[code];
    } else {
      state.translationLanguages[code] = name;
    }

    renderTranslationLanguages();
    updateTranslationDropdownLabel();
    saveState();
  });

  $(document).on("keyup", ".translation-language-search", function () {
    const value = $(this).val().toLowerCase();
    $(".translation-language-option").each(function () {
      $(this).toggle($(this).text().toLowerCase().includes(value));
    });
  });

  if (!state.postTypes) {
    state.postTypes = {};
    if (state.postType) {
      state.postTypes[state.postType] = state.postType;
    }
  }

  function renderSelectedPostTypes() {
    var labels = [];
    var hiddenInputs = "";

    $(".post-type-option").removeClass("active");

    Object.keys(state.postTypes || {}).forEach(function (pt) {
      var label = state.postTypes[pt] || pt;
      labels.push(label);
      hiddenInputs +=
        '<input type="hidden" name="post_types[]" value="' + pt + '">';
      // mark option active
      $('.post-type-option[data-post-type="' + pt + '"]').addClass("active");
    });

    $("#postTypeDropdownBtn").text(
      labels.length ? labels.join(", ") : "Select Post Type"
    );
    $(".selected-post-types").html(hiddenInputs);

    if ($("#aimt_post_type").length) {
      var first = Object.keys(state.postTypes || {})[0] || "";
      $("#aimt_post_type").val(first);
    } else {
      var first = Object.keys(state.postTypes || {})[0] || "";
      $("<input>")
        .attr({
          type: "hidden",
          id: "aimt_post_type",
          name: "aimt_post_type",
          value: first,
        })
        .appendTo(".selected-post-types");
    }
  }

  function updatePostTypeDropdownLabel() {
    var labels = Object.keys(state.postTypes || {}).map(function (pt) {
      return state.postTypes[pt];
    });
    $("#postTypeDropdownBtn").text(
      labels.length ? labels.join(", ") : "Select Post Type"
    );
  }

  $(document).off("click", ".post-type-option");
  $(document).on("click", ".post-type-option", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const $el = $(this);
    const postType = $el.data("post-type");
    const label = $el.data("name") || $el.text().trim();
    console.log("post-type click:", postType, label);

    if (!state.postTypes || Array.isArray(state.postTypes)) {
      state.postTypes = {};
    }

    // toggle
    if (state.postTypes[postType]) {
      delete state.postTypes[postType];
    } else {
      state.postTypes[postType] = label || postType;
    }

    renderSelectedPostTypes();
    updatePostTypeDropdownLabel();
    saveState();
  });

  try {
    renderSelectedPostTypes();
  } catch (e) { }

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
    e.stopPropagation();
    const next = $(this).data("next");

    // Validation before navigation
    if (next === "register-multilang") {
      const hasDefaultLangs =
        state.selectedLanguages &&
        Object.keys(state.selectedLanguages).length > 0;
      if (!hasDefaultLangs) {
        alert("Please select at least one default language.");
        return false;
      }

      const hasTranslationLangs =
        state.translationLanguages &&
        Object.keys(state.translationLanguages).length > 0;
      if (!hasTranslationLangs) {
        alert("Please select at least one translation language.");
        return false;
      }

      const hasPostType =
        state.postTypes &&
        Object.keys(state.postTypes).length > 0;
      if (!hasPostType) {
        alert("Please select a post type.");
        return false;
      }
    }

    if (next === "translation-mode") {
      if (!state.wpmlKey || !state.wpmlKey.trim()) {
        alert("Please enter your AI multi language translation registration key.");
        return false;
      }
    }

    if (next === "finished") {
      if (!state.translationMode || !state.translationMode.trim()) {
        alert("Please choose a translation mode.");
        return false;
      }
    }

    navigateToStep(next);
  });

  $(".prev-step").on("click", function (e) {
    e.preventDefault();
    const prev = $(this).data("prev");
    navigateToStep(prev);
  });

  $(
    '<button class="button button-secondary" id="aimt-clear-state" style="margin-left:10px;">Clear All Data</button>'
  )
    .insertAfter(".step-finished .button-primary")
    .on("click", function (e) {
      e.preventDefault();
      if (confirm("Are you sure you want to clear all onboarding data?")) {
        clearState();
      }
    });

  window.testOnboardingStorage = function () {
    console.clear();
    console.log("=== AIMT Onboarding Storage Test ===");

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "aimt_save_onboarding",
        nonce: aimtNonce,
        state: JSON.stringify({
          test: "test_data",
          timestamp: new Date().toISOString(),
        }),
      },
      success: function (response) {
        console.log("✅ Save test response:", response);

        setTimeout(function () {
          console.log("\n✅ Loading test data...");
          $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
              action: "aimt_load_onboarding",
              nonce: aimtNonce,
            },
            success: function (loadResponse) {
              console.log("✅ Load test response:", loadResponse);
              alert("✅ Test completed! Check console for details.");
            },
          });
        }, 1000);
      },
      error: function (xhr, status, error) {
        console.error("❌ Test failed:", error);
        alert("❌ Test failed! Check console for errors.");
      },
    });
  };
});
