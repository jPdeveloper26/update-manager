/**
 * Plugin Update Manager Admin JavaScript
 *
 * @package Plugin_Update_Manager
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Handle disable link click
        $(document).on('click', '.pum-disable-link', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var pluginFile = $link.data('plugin');
            var $row = $link.closest('tr');
            
            // Get plugin name - try multiple possible locations
            var pluginName = '';
            if ($row.find('.plugin-title strong').length) {
                pluginName = $row.find('.plugin-title strong').text();
            } else if ($row.find('td strong').first().length) {
                pluginName = $row.find('td strong').first().text();
            } else {
                pluginName = pluginFile; // Fallback to file name
            }
            
            // Get plugin version - check multiple patterns
            var pluginVersion = 'Unknown';
            var versionText = $row.text();
            var versionMatch = versionText.match(/Version\s+([0-9.]+)/i);
            
            if (versionMatch && versionMatch[1]) {
                pluginVersion = versionMatch[1];
            }
            
            // Show disable dialog
            showDisableDialog(pluginFile, pluginName, pluginVersion);
        });
        
        // Handle enable link click
        $(document).on('click', '.pum-enable-link', function(e) {
            e.preventDefault();
            
            if (!confirm(pum_ajax.confirm_enable)) {
                return;
            }
            
            var $link = $(this);
            var pluginFile = $link.data('plugin');
            
            // Add loading spinner
            $link.after('<span class="pum-loading"></span>');
            $link.hide();
            
            // Send AJAX request
            $.post(pum_ajax.ajax_url, {
                action: 'pum_enable_plugin',
                plugin_file: pluginFile,
                nonce: pum_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    // Change link to disable link
                    $link.text(pum_ajax.disable_text)
                        .removeClass('pum-enable-link')
                        .addClass('pum-disable-link');
                    
                    // Remove disabled indicator
                    $link.closest('tr').find('.pum-disabled-indicator').fadeOut(function() {
                        $(this).remove();
                    });
                    
                    // Remove disabled update notice if exists
                    var $updateRow = $link.closest('tr').next('.pum-disabled-update-notice');
                    if ($updateRow.length) {
                        $updateRow.fadeOut(function() {
                            $(this).remove();
                        });
                    }
                    
                    // Show success message
                    showNotice(response.data.message, 'success');
                } else {
                    showNotice(response.data.message || pum_ajax.error_message, 'error');
                }
            })
            .fail(function() {
                showNotice(pum_ajax.error_message, 'error');
            })
            .always(function() {
                $('.pum-loading').remove();
                $link.show();
            });
        });
        
        /**
         * Show disable dialog
         */
        function showDisableDialog(pluginFile, pluginName, pluginVersion) {
            // Check if dialog exists, if not create it
            if (!$('#pum-disable-dialog').length) {
                var dialogHtml = '<div id="pum-disable-dialog">' +
                    '<h3>' + pum_ajax.disable_dialog_title + '</h3>' +
                    '<form id="pum-disable-form">' +
                    '<div class="pum-dialog-row"><label>' + pum_ajax.plugin_label + '</label><strong id="pum-plugin-name"></strong></div>' +
                    '<div class="pum-dialog-row"><label>' + pum_ajax.version_label + '</label><strong id="pum-disable-version"></strong></div>' +
                    '<div class="pum-dialog-row"><label for="pum-disable-note">' + pum_ajax.reason_label + '</label>' +
                    '<textarea id="pum-disable-note" placeholder="' + pum_ajax.reason_placeholder + '" required></textarea></div>' +
                    '<div class="pum-dialog-actions">' +
                    '<button type="submit" class="button button-primary">' + pum_ajax.disable_button + '</button>' +
                    '<button type="button" class="button pum-cancel-btn">' + pum_ajax.cancel_button + '</button>' +
                    '</div>' +
                    '<input type="hidden" id="pum-plugin-file" value="">' +
                    '</form></div>';
                
                $('body').append('<div id="pum-disable-overlay" style="display:none;"></div>');
                $('#pum-disable-overlay').append(dialogHtml);
            }
            
            // Set dialog values
            $('#pum-plugin-name').text(pluginName);
            $('#pum-disable-version').text(pluginVersion);
            $('#pum-plugin-file').val(pluginFile);
            $('#pum-disable-note').val('');
            
            // Show overlay and dialog with animation
            $('#pum-disable-overlay').fadeIn(300);
            $('#pum-disable-dialog').css({
                'position': 'fixed',
                'top': '50%',
                'left': '50%',
                'transform': 'translate(-50%, -50%)',
                'z-index': '10000'
            });
            
            // Focus on textarea
            setTimeout(function() {
                $('#pum-disable-note').focus();
            }, 300);
            
            // Handle form submission
            $('#pum-disable-form').off('submit').on('submit', function(e) {
                e.preventDefault();
                
                var note = $('#pum-disable-note').val().trim();
                
                if (!note) {
                    alert(pum_ajax.note_required || 'Please provide a reason for disabling updates for this plugin.');
                    return;
                }
                
                // Hide dialog
                $('#pum-disable-overlay').fadeOut();
                
                // Find the link and add loading spinner
                var $link = $('.pum-disable-link[data-plugin="' + pluginFile + '"]');
                $link.after('<span class="pum-loading"></span>');
                $link.hide();
                
                // Send AJAX request
                $.post(pum_ajax.ajax_url, {
                    action: 'pum_disable_plugin',
                    plugin_file: pluginFile,
                    version: pluginVersion,
                    note: note,
                    nonce: pum_ajax.nonce
                })
                .done(function(response) {
                    if (response.success) {
                        // Change link to enable link
                        $link.text(pum_ajax.enable_text)
                            .removeClass('pum-disable-link')
                            .addClass('pum-enable-link');
                        
                        // Reload page to show disabled indicator
                        location.reload();
                    } else {
                        showNotice(response.data.message || pum_ajax.error_message, 'error');
                    }
                })
                .fail(function() {
                    showNotice(pum_ajax.error_message, 'error');
                })
                .always(function() {
                    $('.pum-loading').remove();
                    $link.show();
                });
            });
            
            // Handle overlay click to close
            $('#pum-disable-overlay').off('click').on('click', function(e) {
                if ($(e.target).is('#pum-disable-overlay')) {
                    $(this).fadeOut(300);
                }
            });
            
            // Handle cancel button
            $(document).on('click', '.pum-cancel-btn', function() {
                $('#pum-disable-overlay').fadeOut(300);
            });
            
            // Handle ESC key
            $(document).off('keyup.pum').on('keyup.pum', function(e) {
                if (e.keyCode === 27) {
                    $('#pum-disable-overlay').fadeOut(300);
                    $(document).off('keyup.pum');
                }
            });
        }
        
        /**
         * Show admin notice
         */
        function showNotice(message, type) {
            // Remove any existing notices
            $('.pum-notice').remove();
            
            var noticeClass = type === 'success' ? 'success' : 'error';
            var icon = type === 'success' ? '✓' : '✕';
            var $notice = $('<div class="pum-notice ' + noticeClass + '">' +
                '<span style="margin-right: 10px; font-size: 18px;">' + icon + '</span>' +
                '<span>' + message + '</span>' +
                '</div>');
            
            $('body').append($notice);
            
            // Animate in
            setTimeout(function() {
                $notice.addClass('show');
            }, 10);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Click to dismiss
            $notice.on('click', function() {
                $(this).fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }
        
    });

})(jQuery);