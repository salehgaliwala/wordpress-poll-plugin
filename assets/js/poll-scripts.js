/**
 * CTS Daily Poll – Front-end JavaScript
 * Handles vote submission, result display, and admin copy-shortcode.
 */
(function($) {
    'use strict';

    /**
     * Submit a vote via AJAX.
     */
    function submitVote(form) {
        var widget   = form.closest('.cts-poll-widget');
        var pollId   = widget.data('poll-id');
        var selected = form.find('input[name="cts_poll_option"]:checked');
        var button   = form.find('.cts-poll-vote-btn');
        var message  = form.find('.cts-poll-message');

        // Validate selection.
        if (selected.length === 0) {
            showMessage(message, ctsPollVars.noOptionSelected || 'Please select an option.', 'error');
            return;
        }

        var optionIndex = selected.val();

        // Disable button and show loading.
        button.prop('disabled', true);
        button.append('<span class="cts-poll-loading"></span>');

        $.ajax({
            url: ctsPollVars.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cts_poll_vote',
                poll_id: pollId,
                option_index: optionIndex,
                nonce: ctsPollVars.nonce
            },
            success: function(response) {
                button.find('.cts-poll-loading').remove();
                button.prop('disabled', false);

                if (response.success) {
                    // Hide form, show results.
                    form.hide();
                    var resultsContainer = widget.find('.cts-poll-results');
                    updateResults(resultsContainer, response.data.results, response.data.total_votes);
                    resultsContainer.show();

                    // Set cookie via JS as additional client-side check.
                    document.cookie = 'cts_poll_voted_' + pollId + '=1; path=/; max-age=' + (30 * 24 * 60 * 60) + '; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
                } else {
                    showMessage(message, response.data.message, 'error');
                }
            },
            error: function() {
                button.find('.cts-poll-loading').remove();
                button.prop('disabled', false);
                showMessage(message, ctsPollVars.errorMessage || 'An error occurred. Please try again.', 'error');
            }
        });
    }

    /**
     * Update the results container with vote data.
     */
    function updateResults(container, results, totalVotes) {
        var html = '';

        if (!results || results.length === 0) {
            html = '<p class="cts-poll-no-votes">' + (ctsPollVars.noVotesMessage || 'No votes yet.') + '</p>';
        } else {
            html += '<div class="cts-poll-results-bars">';
            $.each(results, function(i, item) {
                html += '<div class="cts-poll-bar-row">';
                html += '<span class="cts-poll-bar-label">' + escapeHtml(item.option) + '</span>';
                html += '<div class="cts-poll-bar-track"><div class="cts-poll-bar-fill" style="width:' + item.percent + '%;"></div></div>';
                html += '<span class="cts-poll-bar-stats">' + item.votes + ' (' + item.percent + '%)</span>';
                html += '</div>';
            });
            html += '</div>';
            html += '<p class="cts-poll-total-text">' + (ctsPollVars.totalVotesText || 'Total votes:') + ' ' + totalVotes + '</p>';
        }

        container.html(html);
    }

    /**
     * Show a message inside the message container.
     */
    function showMessage(container, text, type) {
        container.removeClass('error success').addClass(type).text(text).show();
    }

    /**
     * Simple HTML escaping.
     */
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ----------------------------------------------------------------
    // Event Bindings
    // ----------------------------------------------------------------

    // Vote form submission.
    $(document).on('submit', '.cts-poll-form', function(e) {
        e.preventDefault();
        submitVote($(this));
    });

    // Admin: Copy shortcode row action.
    $(document).on('click', '.cts-poll-copy-shortcode', function(e) {
        e.preventDefault();
        var shortcode = $(this).data('shortcode');

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(shortcode).then(function() {
                var original = $(this).text();
                $(this).text(ctsPollVars.copiedText || 'Copied!');
                var self = this;
                setTimeout(function() {
                    $(self).text(original);
                }, 2000);
            }.bind(this));
        } else {
            // Fallback for older browsers.
            var textarea = document.createElement('textarea');
            textarea.value = shortcode;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                var original = $(this).text();
                $(this).text(ctsPollVars.copiedText || 'Copied!');
                setTimeout(function() {
                    $(this).text(original);
                }.bind(this), 2000);
            } catch (err) {
                // Ignore.
            }
            document.body.removeChild(textarea);
        }
    });

})(jQuery);