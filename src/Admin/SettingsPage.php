<?php

declare(strict_types=1);

namespace WordPress\OpenRouterAiProvider\Admin;

/**
 * Admin settings page for the OpenRouter AI Provider.
 *
 * @since 1.1.0
 */
class SettingsPage
{
    private const OPTION_API_KEY      = 'connectors_ai_openrouter_api_key';
    private const OPTION_DEFAULT_MODEL = 'openrouter_ai_default_model';
    private const SETTINGS_GROUP      = 'openrouter_ai_provider_settings';
    private const PAGE_SLUG           = 'openrouter-ai-provider';

    public static function register(): void
    {
        add_action('admin_menu', [static::class, 'addMenuPage']);
        add_action('admin_init', [static::class, 'registerSettings']);
    }

    public static function addMenuPage(): void
    {
        add_options_page(
            __('OpenRouter AI Provider', 'ai-provider-for-openrouter'),
            __('OpenRouter AI', 'ai-provider-for-openrouter'),
            'manage_options',
            static::PAGE_SLUG,
            [static::class, 'renderPage']
        );
    }

    public static function registerSettings(): void
    {
        register_setting(static::SETTINGS_GROUP, static::OPTION_API_KEY, [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]);

        register_setting(static::SETTINGS_GROUP, static::OPTION_DEFAULT_MODEL, [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]);
    }

    public static function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $apiKey       = get_option(static::OPTION_API_KEY, '');
        $defaultModel = get_option(static::OPTION_DEFAULT_MODEL, '');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('OpenRouter AI Provider Settings', 'ai-provider-for-openrouter'); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields(static::SETTINGS_GROUP); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="or_api_key"><?php esc_html_e('API Key', 'ai-provider-for-openrouter'); ?></label>
                        </th>
                        <td>
                            <input
                                type="password"
                                id="or_api_key"
                                name="<?php echo esc_attr(static::OPTION_API_KEY); ?>"
                                value="<?php echo esc_attr($apiKey); ?>"
                                class="regular-text"
                                autocomplete="new-password"
                            />
                            <p class="description">
                                <?php esc_html_e('Your OpenRouter API key.', 'ai-provider-for-openrouter'); ?>
                                <a href="https://openrouter.ai/settings/keys" target="_blank" rel="noopener">
                                    <?php esc_html_e('Get a key', 'ai-provider-for-openrouter'); ?>
                                </a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="or_model_input"><?php esc_html_e('Default Model', 'ai-provider-for-openrouter'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="or_model_input"
                                name="<?php echo esc_attr(static::OPTION_DEFAULT_MODEL); ?>"
                                value="<?php echo esc_attr($defaultModel); ?>"
                                class="regular-text"
                                placeholder="<?php esc_attr_e('Type to search models…', 'ai-provider-for-openrouter'); ?>"
                                list="or-models-datalist"
                                autocomplete="off"
                            />
                            <datalist id="or-models-datalist"></datalist>
                            <p class="description" id="or-model-load-status">
                                <?php if ($apiKey) : ?>
                                    <?php esc_html_e('Loading models…', 'ai-provider-for-openrouter'); ?>
                                <?php else : ?>
                                    <?php esc_html_e('Enter your API key and save to load available models.', 'ai-provider-for-openrouter'); ?>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <div id="or-model-info" style="display:none; margin: 16px 0 16px 200px; padding: 14px 18px; border: 1px solid #c3c4c7; border-radius: 4px; background: #f9f9f9; max-width: 520px;">
                    <strong id="or-info-name" style="display:block; font-size:14px; margin-bottom:8px;"></strong>
                    <table style="width:100%; border-collapse:collapse; font-size:13px;">
                        <tr id="or-info-row-context">
                            <td style="padding:3px 8px 3px 0; color:#50575e; white-space:nowrap;"><?php esc_html_e('Context length', 'ai-provider-for-openrouter'); ?></td>
                            <td id="or-info-context" style="padding:3px 0;"></td>
                        </tr>
                        <tr id="or-info-row-prompt">
                            <td style="padding:3px 8px 3px 0; color:#50575e; white-space:nowrap;"><?php esc_html_e('Input price', 'ai-provider-for-openrouter'); ?></td>
                            <td id="or-info-prompt" style="padding:3px 0;"></td>
                        </tr>
                        <tr id="or-info-row-completion">
                            <td style="padding:3px 8px 3px 0; color:#50575e; white-space:nowrap;"><?php esc_html_e('Output price', 'ai-provider-for-openrouter'); ?></td>
                            <td id="or-info-completion" style="padding:3px 0;"></td>
                        </tr>
                        <tr id="or-info-row-modality">
                            <td style="padding:3px 8px 3px 0; color:#50575e; white-space:nowrap;"><?php esc_html_e('Modality', 'ai-provider-for-openrouter'); ?></td>
                            <td id="or-info-modality" style="padding:3px 0;"></td>
                        </tr>
                        <tr id="or-info-row-desc">
                            <td style="padding:6px 8px 3px 0; color:#50575e; white-space:nowrap; vertical-align:top;"><?php esc_html_e('Description', 'ai-provider-for-openrouter'); ?></td>
                            <td id="or-info-desc" style="padding:6px 0; color:#3c434a;"></td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(__('Save Settings', 'ai-provider-for-openrouter')); ?>
            </form>
        </div>

        <script>
        (function() {
            var apiKey    = <?php echo wp_json_encode($apiKey); ?>;
            var datalist  = document.getElementById('or-models-datalist');
            var input     = document.getElementById('or_model_input');
            var status    = document.getElementById('or-model-load-status');
            var infoBox   = document.getElementById('or-model-info');
            var allModels = [];

            function fmt(n) {
                return parseFloat(n) === 0 ? 'Free' : '$' + (parseFloat(n) * 1e6).toFixed(4) + ' / 1M tokens';
            }

            function showInfo(model) {
                if (!model) { infoBox.style.display = 'none'; return; }
                document.getElementById('or-info-name').textContent = model.name + ' (' + model.id + ')';
                var ctx = model.context_length ? model.context_length.toLocaleString() + ' tokens' : '—';
                document.getElementById('or-info-context').textContent = ctx;
                var pricing = model.pricing || {};
                document.getElementById('or-info-prompt').textContent     = pricing.prompt     != null ? fmt(pricing.prompt)     : '—';
                document.getElementById('or-info-completion').textContent = pricing.completion != null ? fmt(pricing.completion) : '—';
                var arch = model.architecture || {};
                document.getElementById('or-info-modality').textContent = arch.modality || '—';
                document.getElementById('or-info-desc').textContent     = model.description || '';
                document.getElementById('or-info-row-desc').style.display = model.description ? '' : 'none';
                infoBox.style.display = 'block';
            }

            function onModelChange() {
                var val = input.value.trim();
                if (!val) { infoBox.style.display = 'none'; return; }
                var match = allModels.find(function(m) { return m.id === val; });
                showInfo(match || null);
            }

            input.addEventListener('input', onModelChange);
            input.addEventListener('change', onModelChange);

            if (!apiKey) return;

            fetch('https://openrouter.ai/api/v1/models', {
                headers: { 'Authorization': 'Bearer ' + apiKey }
            })
            .then(function(r) {
                if (!r.ok) throw new Error(r.statusText);
                return r.json();
            })
            .then(function(data) {
                allModels = (data && data.data) ? data.data : [];
                datalist.innerHTML = '';
                allModels.forEach(function(m) {
                    var opt = document.createElement('option');
                    opt.value = m.id;
                    opt.label = m.name;
                    datalist.appendChild(opt);
                });
                status.textContent = allModels.length + ' <?php echo esc_js(__('models loaded.', 'ai-provider-for-openrouter')); ?>';
                onModelChange();
            })
            .catch(function(err) {
                status.textContent = '<?php echo esc_js(__('Failed to load models. Check your API key.', 'ai-provider-for-openrouter')); ?>';
            });
        }());
        </script>
        <?php
    }
}
