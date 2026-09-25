(function ($) {
    'use strict';

    $(document).ready(function () {
        var lastFocusedTextarea = null;

        $(document).on('focus', 'textarea', function () {
            lastFocusedTextarea = this;
        });

        function scrollActiveTabToCenter(smooth) {
            var $nav = $('.affichat-nav-tabs');
            if (!$nav.length) return;

            var $active = $nav.find('.affichat-tab-link.active');
            if (!$active.length) return;

            var navEl = $nav[0];
            var activeEl = $active[0];

            var navRect = navEl.getBoundingClientRect();
            var activeRect = activeEl.getBoundingClientRect();

            var relativeLeft = activeRect.left - navRect.left + navEl.scrollLeft;
            var scrollTarget = Math.round(relativeLeft - (navEl.clientWidth / 2) + (activeEl.offsetWidth / 2));

            if (scrollTarget < 0) {
                scrollTarget = 0;
            }
            var maxScroll = Math.max(0, navEl.scrollWidth - navEl.clientWidth);
            if (scrollTarget > maxScroll) {
                scrollTarget = maxScroll;
            }

            if (smooth && typeof navEl.scrollTo === 'function') {
                try {
                    navEl.scrollTo({ left: scrollTarget, behavior: 'smooth' });
                } catch (err) {
                    $nav.stop().animate({ scrollLeft: scrollTarget }, 240);
                }
            } else {
                navEl.scrollLeft = scrollTarget;
            }
        }

        // Staggered calls account for late webfont or layout shifts on mobile
        scrollActiveTabToCenter(false);
        setTimeout(function () { scrollActiveTabToCenter(false); }, 50);
        setTimeout(function () { scrollActiveTabToCenter(false); }, 180);
        setTimeout(function () { scrollActiveTabToCenter(false); }, 400);

        $(window).on('load resize orientationchange', function () {
            scrollActiveTabToCenter(false);
        });

        $(document).on('click', '.affichat-tab-link', function () {
            $('.affichat-tab-link').removeClass('active');
            $(this).addClass('active');
            scrollActiveTabToCenter(true);
        });

        function updateWaPreview() {
            var phone = $('#affichat_quick_phone').val();
            var msg   = $('#affichat_quick_message').val();

            if (phone && phone.trim()) {
                $('#affichat-preview-title').text(phone.trim());
            } else {
                $('#affichat-preview-title').text('Penerima Pesan');
            }

            if (msg && msg.trim()) {
                var escaped = $('<div>').text(msg).html();
                var formatted = escaped
                    .replace(/\*(.*?)\*/g, '<strong>$1</strong>')
                    .replace(/_(.*?)_/g, '<em>$1</em>')
                    .replace(/~(.*?)~/g, '<del>$1</del>')
                    .replace(/\n/g, '<br/>');
                $('#affichat-preview-text').html(formatted);
            } else {
                $('#affichat-preview-text').text('Ketik pesan di formulir untuk melihat pratinjau langsung...');
            }
        }

        $('#affichat_quick_phone, #affichat_quick_message').on('input keyup change paste', updateWaPreview);

        $(document).on('click', '.affichat-tag-chip', function (e) {
            e.preventDefault();
            var tag = $(this).data('tag') || $(this).text().trim();
            var textarea = lastFocusedTextarea || $('textarea:visible').first()[0];

            if (textarea) {
                var startPos = textarea.selectionStart;
                var endPos = textarea.selectionEnd;
                var text = textarea.value;

                textarea.value = text.substring(0, startPos) + tag + text.substring(endPos, text.length);
                textarea.selectionStart = startPos + tag.length;
                textarea.selectionEnd = startPos + tag.length;
                textarea.focus();

                if (textarea.id === 'affichat_quick_message') {
                    updateWaPreview();
                }
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(tag);
            }
        });

        $('#affichat-wp-btn-test').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $result = $('#affichat-wp-test-result');

            var baseUrl   = ($('#affichat_wp_base_url').length ? $('#affichat_wp_base_url').val() : '') || 'https://chat.affidev.com';
            var apiKey    = $('#affichat_wp_api_key').val() || '';
            var sessionId = $('#affichat_wp_session_id').val() || 'default';

            $btn.prop('disabled', true).find('span').text(affichat_wp_vars.i18n.testing);
            $result.removeClass('success error').hide().text('');

            $.ajax({
                url: affichat_wp_vars.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'affichat_wp_test_connection',
                    nonce: affichat_wp_vars.nonce,
                    base_url: baseUrl,
                    api_key: apiKey,
                    session_id: sessionId
                },
                success: function (res) {
                    $btn.prop('disabled', false).find('span').text(affichat_wp_vars.i18n.test_btn);
                    if (res && res.success) {
                        $result.addClass('success').text(res.data.message || 'Koneksi ke gateway berhasil!').fadeIn();
                    } else {
                        var msg = (res && res.data && res.data.message) ? res.data.message : affichat_wp_vars.i18n.failed;
                        $result.addClass('error').text(msg).fadeIn();
                    }
                },
                error: function (xhr, status, error) {
                    $btn.prop('disabled', false).find('span').text(affichat_wp_vars.i18n.test_btn);
                    $result.addClass('error').text('AJAX Error: ' + (error || status)).fadeIn();
                }
            });
        });

        $('#affichat-wp-quick-send-form').on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#affichat-btn-quick-send');
            var $result = $('#affichat-quick-send-result');

            var phone   = $('#affichat_quick_phone').val();
            var message = $('#affichat_quick_message').val();

            $btn.prop('disabled', true).text(affichat_wp_vars.i18n.sending);
            $result.removeClass('success error').hide().text('');

            $.ajax({
                url: affichat_wp_vars.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'affichat_wp_quick_send',
                    nonce: affichat_wp_vars.nonce,
                    phone: phone,
                    message: message
                },
                success: function (res) {
                    $btn.prop('disabled', false).text(affichat_wp_vars.i18n.send_btn);
                    if (res && res.success) {
                        $result.addClass('success').text(res.data.message || 'Pesan berhasil dikirim!').fadeIn();
                        $('#affichat_quick_message').val('');
                        updateWaPreview();
                    } else {
                        var msg = (res && res.data && res.data.message) ? res.data.message : 'Gagal mengirim pesan.';
                        $result.addClass('error').text(msg).fadeIn();
                    }
                },
                error: function (xhr, status, error) {
                    $btn.prop('disabled', false).text(affichat_wp_vars.i18n.send_btn);
                    $result.addClass('error').text('AJAX Error: ' + (error || status)).fadeIn();
                }
            });
        });

        window.showToast = function (title, message, type) {
            type = type || 'success';
            var $container = $('#affichat-toast-container');
            if (!$container.length) {
                $container = $('<div id="affichat-toast-container" class="affichat-toast-container"></div>').appendTo('body');
            }

            var $toast = $(
                '<div class="affichat-toast affichat-toast-' + type + '">' +
                    '<div class="affichat-toast-content">' +
                        '<div class="affichat-toast-title">' + $('<div>').text(title).html() + '</div>' +
                        '<div class="affichat-toast-desc">' + $('<div>').text(message).html() + '</div>' +
                    '</div>' +
                '</div>'
            );

            $container.append($toast);
            setTimeout(function () {
                $toast.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 4000);
        };

        window.showConfirmModal = function (options) {
            options = options || {};
            var title       = options.title || 'Konfirmasi';
            var message     = options.message || 'Apakah Anda yakin ingin melanjutkan?';
            var confirmText = options.confirmText || 'Lanjutkan';
            var cancelText  = options.cancelText || 'Batal';
            var type        = options.type || 'warning';

            return new Promise(function (resolve) {
                var $backdrop = $(
                    '<div class="affichat-modal-backdrop">' +
                        '<div class="affichat-modal-box">' +
                            '<div class="affichat-modal-body">' +
                                '<div class="affichat-modal-header">' +
                                    '<div class="affichat-modal-icon ' + type + '">' +
                                        '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>' +
                                    '</div>' +
                                    '<h4 class="affichat-modal-title">' + $('<div>').text(title).html() + '</h4>' +
                                '</div>' +
                                '<p class="affichat-modal-message">' + $('<div>').text(message).html() + '</p>' +
                                '<div class="affichat-modal-actions">' +
                                    '<button type="button" class="affichat-modal-btn affichat-modal-btn-cancel">' + $('<div>').text(cancelText).html() + '</button>' +
                                    '<button type="button" class="affichat-modal-btn affichat-modal-btn-confirm ' + type + '">' + $('<div>').text(confirmText).html() + '</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );

                $('body').append($backdrop);
                setTimeout(function () {
                    $backdrop.addClass('active');
                }, 10);

                function cleanup(confirmed) {
                    $backdrop.removeClass('active');
                    setTimeout(function () {
                        $backdrop.remove();
                        resolve(confirmed);
                    }, 200);
                }

                $backdrop.find('.affichat-modal-btn-confirm').on('click', function () {
                    cleanup(true);
                });

                $backdrop.find('.affichat-modal-btn-cancel').on('click', function () {
                    cleanup(false);
                });

                $backdrop.on('click', function (e) {
                    if ($(e.target).hasClass('affichat-modal-backdrop')) {
                        cleanup(false);
                    }
                });
            });
        };

        $(document).on('click', '.affichat-btn-reset-single', async function (e) {
            e.preventDefault();
            var targetId = $(this).data('reset-target');
            var defaultVal = $(this).data('default');
            if (!targetId || typeof defaultVal === 'undefined') return;

            var ok = await window.showConfirmModal({
                title: 'Reset Template ke Default?',
                message: 'Teks template pada form ini akan dikembalikan ke teks rekomendasi standar bawaan.',
                confirmText: 'Ya, Reset',
                cancelText: 'Batal',
                type: 'warning'
            });
            if (!ok) return;

            var $target = $('#' + targetId);
            if ($target.length) {
                $target.val(defaultVal).trigger('input').trigger('change');
                $target.addClass('affichat-highlight-pulse');
                setTimeout(function () {
                    $target.removeClass('affichat-highlight-pulse');
                }, 1200);
                window.showToast('Template Direset', 'Template berhasil dikembalikan ke rekomendasi default.', 'success');
            }
        });

        $(document).on('click', '.affichat-btn-reset-all', async function (e) {
            e.preventDefault();
            var section = $(this).data('reset-section');

            var ok = await window.showConfirmModal({
                title: 'Reset Semua Pengaturan Tab Ini?',
                message: 'Semua checklist dan template pesan pada tab ini akan dikembalikan ke pengaturan default awal.',
                confirmText: 'Ya, Reset Semua',
                cancelText: 'Batal',
                type: 'danger'
            });
            if (!ok) return;

            if (section === 'forms') {
                $('input[name^="affichat_wp_form_"], input[name^="affichat_wp_jetform"], input[name^="affichat_wp_elementor"], input[name^="affichat_wp_cf7"], input[name^="affichat_wp_wpforms"]').prop('checked', false);
                $('.affichat-btn-reset-single[data-reset-target]').each(function () {
                    var tid = $(this).data('reset-target');
                    var dval = $(this).data('default');
                    if (tid && typeof dval !== 'undefined') {
                        $('#' + tid).val(dval).addClass('affichat-highlight-pulse');
                    }
                });
                setTimeout(function () {
                    $('.affichat-highlight-pulse').removeClass('affichat-highlight-pulse');
                }, 1200);
                window.showToast('Reset Selesai', 'Pengaturan form berhasil di-reset. Klik "Simpan Pengaturan" untuk menyimpan ke database.', 'success');
            } else if (section === 'woocommerce') {
                $('.affichat-btn-reset-single[data-reset-target]').each(function () {
                    var tid = $(this).data('reset-target');
                    var dval = $(this).data('default');
                    if (tid && typeof dval !== 'undefined') {
                        $('#' + tid).val(dval).addClass('affichat-highlight-pulse');
                    }
                });
                setTimeout(function () {
                    $('.affichat-highlight-pulse').removeClass('affichat-highlight-pulse');
                }, 1200);
                window.showToast('Reset Selesai', 'Semua template notifikasi WooCommerce berhasil di-reset ke default.', 'success');
            } else if (section === 'general') {
                $('#affichat_wp_session_id').val('default');
                window.showToast('Reset Selesai', 'ID Sesi WhatsApp telah di-reset ke nilai default.', 'info');
            }
        });
    });
})(jQuery);
