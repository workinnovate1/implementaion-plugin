jQuery(document).ready(function ($) {
  // Ensure aimtData exists
  if (typeof aimtData === "undefined" || !aimtData.show_alert) {
    console.warn("aimtData is not available");

    if (typeof aimtData !== "undefined" && aimtData.elementor_nodes) {
      console.table(aimtData.elementor_nodes);
    }

    return;
  }

  if (aimtData.elementor_nodes && aimtData.elementor_nodes.length > 0) {
    console.log("Elementor text nodes for this post:");
    console.table(aimtData.elementor_nodes);
    console.log('Raw Elementor Data:', aimtData.raw_elementor_data);
console.log('Decoded Elementor Data:', aimtData.decoded_elementor_data);
  } else {
    console.warn("No Elementor nodes found for this post.");
  }

  var langs = aimtData.translation_languages || {};
  var modalId = "aimt-translate-modal";
  var currentPostId = aimtData.post_id;

  // Add modal styles if not already added
  if (!document.getElementById("aimt-modal-styles")) {
    var style = document.createElement("style");
    style.id = "aimt-modal-styles";
    style.innerHTML = `
.aimt-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(17, 24, 39, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99999;
  backdrop-filter: blur(4px);
}

.aimt-modal {
  background: #ffffff;
  padding: 32px;
  border-radius: 12px;
  max-width: 560px;
  width: 100%;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  border: 1px solid #e5e7eb;
  animation: aimtFadeUp 0.25s ease;
  font-family: "Inter", sans-serif;
  overflow: visible;
  position: relative;
}

@keyframes aimtFadeUp {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.aimt-modal h3 {
  margin: 0 0 8px 0;
  font-size: 22px;
  font-weight: 700;
  color: #111827;
}

.aimt-modal p {
  font-size: 14px;
  color: #4b5563;
  line-height: 1.6;
  margin-bottom: 20px;
}

.aimt-lang-list {
  width: 100%;
  margin-bottom: 24px;
  position: relative;
  overflow: visible;
}

.aimt-multiselect-wrapper {
  position: relative;
  width: 100%;
  overflow: visible;
}

.aimt-multiselect-trigger {
  width: 94%;
  min-height: 48px;
  padding: 12px 40px 12px 14px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: #ffffff;
  font-size: 14px;
  font-weight: 500;
  color: #1f2937;
  cursor: pointer;
  font-family: "Inter", sans-serif;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}

.aimt-multiselect-trigger:hover {
  border-color: #0073aa;
}

.aimt-multiselect-trigger.active {
  border-color: #0073aa;
  box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
}

.aimt-multiselect-trigger::after {
  content: "";
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  width: 0;
  height: 0;
  border-left: 6px solid transparent;
  border-right: 6px solid transparent;
  border-top: 8px solid #6b7280;
  transition: transform 0.2s ease;
}

.aimt-multiselect-trigger.active::after {
  transform: translateY(-50%) rotate(180deg);
}

.aimt-multiselect-placeholder {
  color: #9ca3af;
  font-weight: 400;
}

.aimt-selected-tag {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  background: #0073aa;
  color: #ffffff;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  gap: 6px;
}

.aimt-selected-tag .aimt-tag-remove {
  cursor: pointer;
  font-weight: 700;
  font-size: 16px;
  line-height: 1;
  opacity: 0.8;
  transition: opacity 0.15s ease;
}

.aimt-selected-tag .aimt-tag-remove:hover {
  opacity: 1;
}

.aimt-multiselect-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #ffffff;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  max-height: 260px;
  overflow-y: auto;
  overflow-x: hidden;
  z-index: 100000;
  display: none;
  animation: aimtFadeUp 0.2s ease;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  margin: 0;
}

.aimt-multiselect-dropdown.upward {
  top: auto;
  bottom: calc(100% + 4px);
}

.aimt-multiselect-dropdown.show {
  display: block;
}

.aimt-multiselect-dropdown::-webkit-scrollbar {
  width: 8px;
}

.aimt-multiselect-dropdown::-webkit-scrollbar-track {
  background: #f9fafb;
  border-radius: 4px;
}

.aimt-multiselect-dropdown::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

.aimt-multiselect-dropdown::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

.aimt-dropdown-item {
  padding: 12px 14px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #1f2937;
  transition: all 0.15s ease;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
}

.aimt-dropdown-item:hover {
  background: #e8f4ff;
}

.aimt-dropdown-item.selected {
  background: #f0f9ff;
  color: #0073aa;
  font-weight: 600;
}

.aimt-dropdown-item.selected::after {
  content: "✓";
  color: #0073aa;
  font-weight: 700;
  font-size: 16px;
  margin-left: 8px;
}

.aimt-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.aimt-actions .button {
  padding: 10px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.2s ease;
}

.aimt-actions .button:not(.button-primary) {
  background: #f3f4f6;
  color: #374151;
}

.aimt-actions .button:not(.button-primary):hover {
  background: #e5e7eb;
}

.aimt-actions .button-primary {
  background: linear-gradient(135deg, #0073aa 0%, #005a8a 100%);
  color: #ffffff;
  box-shadow: 0 6px 18px rgba(0, 115, 170, 0.3);
}

.aimt-actions .button-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 28px rgba(0, 115, 170, 0.35);
}
`;

    document.head.appendChild(style);
  }

  // Function to create and show the translation modal
  function showTranslationModal() {
    // Remove any existing modal first
    $("#" + modalId + "-overlay").remove();

    // Clear alert flag if it was set
    if (aimtData.show_alert) {
      $.post(aimtData.ajax_url, {
        action: "aimt_clear_alert_flag",
        post_id: currentPostId,
        nonce: aimtData.nonce,
      });
    }

    var overlay = $("<div/>", {
      class: "aimt-modal-overlay",
      id: modalId + "-overlay",
    });
    var modal = $("<div/>", { class: "aimt-modal", id: modalId });

    // create debug container only after modal exists
    var debugContainer = $("<pre/>")
      .css({
        "max-height": "200px",
        overflow: "auto",
        background: "#f3f4f6",
        padding: "12px",
        "border-radius": "8px",
        "margin-bottom": "16px",
      })
      .text(JSON.stringify(aimtData.elementor_nodes || [], null, 2));

    modal.append(debugContainer);

    var title = $("<h3/>").text("Translate this post");
    var desc = $("<p/>").text(
      "Would you like to translate this post into one or more of your configured languages? Select languages:",
    );

    var langList = $("<div/>", { class: "aimt-lang-list" });
    if (Object.keys(langs).length === 0) {
      langList.append(
        $("<p/>").text(
          "No translation languages configured. You can add languages from the plugin settings.",
        ),
      );
    } else {
      var wrapper = $("<div/>", { class: "aimt-multiselect-wrapper" });
      var trigger = $("<div/>", {
        class: "aimt-multiselect-trigger",
        id: "aimt-multiselect-trigger",
      });
      var placeholder = $("<span/>", {
        class: "aimt-multiselect-placeholder",
      }).text("Select languages...");
      trigger.append(placeholder);

      var dropdown = $("<div/>", {
        class: "aimt-multiselect-dropdown",
        id: "aimt-multiselect-dropdown",
      });

      Object.keys(langs).forEach(function (code) {
        var label = langs[code] || code;
        var displayText = label + " (" + code.toUpperCase() + ")";
        var item = $("<div/>", {
          class: "aimt-dropdown-item",
          "data-value": code,
          "data-selected": "false",
        }).text(displayText);
        dropdown.append(item);

        item.on("click", function (e) {
          e.stopPropagation();
          var isSelected = $(this).attr("data-selected") === "true";
          if (isSelected) {
            $(this).removeClass("selected").attr("data-selected", "false");
          } else {
            $(this).addClass("selected").attr("data-selected", "true");
          }
          updateTrigger();
        });
      });

      function updateTrigger() {
        var selected = [];
        $(".aimt-dropdown-item[data-selected='true']").each(function () {
          selected.push($(this).attr("data-value"));
        });

        trigger.empty();
        if (selected.length === 0) {
          trigger.append(placeholder.clone());
        } else {
          selected.forEach(function (code) {
            var label = langs[code] || code;
            var displayText = label + " (" + code.toUpperCase() + ")";
            var tag = $("<span/>", { class: "aimt-selected-tag" });
            tag.append($("<span/>").text(displayText));
            var remove = $("<span/>", {
              class: "aimt-tag-remove",
            }).text("×");
            remove.on("click", function (e) {
              e.stopPropagation();
              $('.aimt-dropdown-item[data-value="' + code + '"]')
                .removeClass("selected")
                .attr("data-selected", "false");
              updateTrigger();
            });
            tag.append(remove);
            trigger.append(tag);
          });
        }
      }

      trigger.on("click", function (e) {
        e.stopPropagation();
        var isActive = trigger.hasClass("active");

        if (!isActive) {
          // Calculate if dropdown should open upward
          var triggerOffset = trigger.offset();
          var triggerHeight = trigger.outerHeight();
          var dropdownHeight = 260; // max-height
          var viewportHeight = $(window).height();
          var spaceBelow = viewportHeight - (triggerOffset.top + triggerHeight);
          var spaceAbove = triggerOffset.top;

          // Remove any existing positioning classes
          dropdown.removeClass("upward");

          // If not enough space below but enough space above, open upward
          if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
            dropdown.addClass("upward");
          }

          // Ensure dropdown width matches trigger exactly
          dropdown.css("width", trigger.outerWidth() + "px");
        }

        trigger.toggleClass("active");
        dropdown.toggleClass("show");
      });

      $(document).on("click", function (e) {
        if (
          !$(e.target).closest(".aimt-multiselect-wrapper").length &&
          !$(e.target).hasClass("aimt-tag-remove")
        ) {
          trigger.removeClass("active");
          dropdown.removeClass("show");
        }
      });

      wrapper.append(trigger).append(dropdown);
      langList.append(wrapper);
    }

    var actions = $("<div/>", { class: "aimt-actions" });
    var cancelBtn = $("<button/>", {
      type: "button",
      class: "button",
      text: "Cancel",
    });
    var translateBtn = $("<button/>", {
      type: "button",
      class: "button button-primary",
      text: "Confirm & Save",
    });

    function closeModal() {
      overlay.remove();
    }

    cancelBtn.on("click", function (e) {
      e.preventDefault();
      closeModal();
      console.log("User chose not to translate the post.");
    });

    translateBtn.on("click", function (e) {
      e.preventDefault();
      var selected = [];
      $(".aimt-dropdown-item[data-selected='true']").each(function () {
        selected.push($(this).attr("data-value"));
      });

      if (selected.length === 0) {
        alert("Please select at least one language to translate.");
        return;
      }

      // Store translation options in localStorage for this post
      var translationData = {
        post_id: currentPostId,
        languages: selected,
        timestamp: Date.now(),
      };

      var storageKey = "aimt_translation_options_" + currentPostId;
      localStorage.setItem(storageKey, JSON.stringify(translationData));

      console.log(
        "Translation options stored for post " + currentPostId + ":",
        translationData,
      );

      // Show success message
      translateBtn.text("Options Saved!").prop("disabled", true);
      setTimeout(function () {
        closeModal();
        // Show WordPress notice if available
        if (typeof wp !== "undefined" && wp.data && wp.data.dispatch) {
          wp.data
            .dispatch("core/notices")
            .createNotice(
              "success",
              "Translation options saved. The post will be translated automatically when you save or update it.",
              { isDismissible: true },
            );
        } else {
          // Fallback: show alert
          alert(
            "Translation options saved. The post will be translated automatically when you save or update it.",
          );
        }
      }, 1000);
    });

    actions.append(cancelBtn).append(translateBtn);
    modal.append(title).append(desc).append(langList).append(actions);
    overlay.append(modal);
    $("body").append(overlay);

    // Close modal when clicking outside
    overlay.on("click", function (e) {
      if ($(e.target).hasClass("aimt-modal-overlay")) {
        closeModal();
      }
    });
  }

  // Show modal automatically if alert flag is set
  if (aimtData.show_alert) {
    console.log(
      "Post or page has been saved. Translation modal should be shown.",
    );
    showTranslationModal();
  }

  // Handle "Translate Now" button click
  $(document).on("click", "#aimt-translate-btn", function (e) {
    e.preventDefault();
    showTranslationModal();
  });

  // Intercept form submit to include translation options
  $(document).on("submit", "#post", function (e) {
    var storageKey = "aimt_translation_options_" + currentPostId;
    var storedData = localStorage.getItem(storageKey);

    if (storedData) {
      try {
        var translationData = JSON.parse(storedData);
        // Add hidden field with translation options
        var hiddenField = $("<input>").attr({
          type: "hidden",
          name: "aimt_translation_options",
          value: JSON.stringify(translationData.languages),
        });
        $(this).append(hiddenField);
        console.log("Translation options added to form:", translationData);

        // Clear localStorage after form is submitted
        // We'll check on page load if translation was successful
        localStorage.removeItem(storageKey);
      } catch (err) {
        console.error("Error parsing translation options:", err);
      }
    }
  });

  // Check on page load if translation was just processed
  // This helps provide feedback to the user
  $(window).on("load", function () {
    var storageKey = "aimt_translation_options_" + currentPostId;
    // If the key doesn't exist, translation might have been processed
    // (This is a simple check - in production you might want a more robust solution)
  });
});
