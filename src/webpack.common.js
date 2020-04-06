/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

const loadJsonFile = require("load-json-file");
const WebpackAssetsManifest = require("../node_modules/webpack-assets-manifest");
const path = require("path");
const polyfills_for_fetch = require("../tools/utils/scripts/ie11-polyfill-names.js")
    .polyfills_for_fetch;
const webpack_configurator = require("../tools/utils/scripts/webpack-configurator.js");
const context = __dirname;
const assets_dir_path = path.resolve(__dirname, "./www/assets/core");
const output = webpack_configurator.configureOutput(assets_dir_path, "/assets/core/");

const manifest_plugin = new WebpackAssetsManifest({
    output: "manifest.json",
    merge: true,
    writeToDisk: true,
    customize(entry) {
        if (entry.key !== "ckeditor.js") {
            return entry;
        }

        return {
            key: entry.key,
            value: `ckeditor-${ckeditor_version}/ckeditor.js`,
        };
    },
});

const pkg = loadJsonFile.sync(path.resolve(__dirname, "package-lock.json"));
const ckeditor_version = pkg.dependencies.ckeditor.version;
const webpack_config_for_ckeditor = {
    entry: {
        ckeditor: "./node_modules/ckeditor/ckeditor.js",
    },
    context,
    output,
    plugins: [
        manifest_plugin,
        webpack_configurator.getCopyPlugin([
            {
                from: path.resolve(__dirname, "./node_modules/ckeditor"),
                to: path.resolve(__dirname, `./www/assets/core/ckeditor-${ckeditor_version}/`),
                toType: "dir",
                ignore: ["**/samples/**", "**/.github/**", "**/*.!(js|css|png)"],
            },
        ]),
    ],
};

const webpack_config_for_flaming_parrot_code = {
    entry: {
        "flamingparrot-with-polyfills": polyfills_for_fetch.concat([
            "./scripts/FlamingParrot/index.js",
        ]),
    },
    context,
    output,
    externals: {
        jquery: "jQuery",
        tuleap: "tuleap",
    },
    resolve: {
        alias: {
            // keymaster-sequence isn't on npm
            "keymaster-sequence": path.resolve(
                __dirname,
                "./scripts/FlamingParrot/keymaster-sequence/keymaster.sequence.min.js"
            ),
            // navbar-history-flamingparrot needs this because TLP is not included in FlamingParrot
            "tlp-fetch": path.resolve(__dirname, "./www/themes/common/tlp/src/js/fetch-wrapper.js"),
        },
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            {
                test: /keymaster\.sequence\.min\.js$/,
                use: "imports-loader?key=keymaster",
            },
        ],
    },
    plugins: [manifest_plugin],
};

const webpack_config_for_rich_text_editor = {
    entry: {
        "rich-text-editor": "./scripts/tuleap/textarea_rte.js",
    },
    context,
    output,
    externals: {
        ckeditor: "CKEDITOR",
        tuleap: "tuleap",
    },
    resolve: {
        alias: {
            "tlp-fetch": path.resolve(__dirname, "./www/themes/common/tlp/src/js/fetch-wrapper.js"),
        },
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files,
        ],
    },
    plugins: [manifest_plugin],
    optimization: {
        // Prototype doesn't like minimization due to the fact
        // that it checks for the presence of "$super" argument
        // during class initialization.
        minimize: false,
    },
};

const webpack_config_for_burning_parrot_code = {
    entry: {
        appearance: "./scripts/account/appearance.ts",
        avatar: "./scripts/account/avatar.ts",
        "keys-tokens": "./scripts/account/keys-tokens.ts",
        "preferences-nav": "./scripts/account/preferences-nav.ts",
        security: "./scripts/account/security.ts",
        timezone: "./scripts/account/timezone.ts",
        dashboard: "./scripts/dashboards/dashboard.js",
        "widget-project-heartbeat": "./scripts/dashboards/widgets/project-heartbeat/index.js",
        "access-denied-error": "./scripts/BurningParrot/src/access-denied-error.js",
        "burning-parrot": "./scripts/BurningParrot/src/index.js",
        "frs-admin-license-agreement": "./scripts/frs/admin/license-agreement.js",
        "project-admin": "./scripts/project/admin/src/index.js",
        "project-admin-ugroups": "./scripts/project/admin/src/project-admin-ugroups.js",
        "project-banner-bp": "./scripts/project/banner/index-bp.ts",
        "project-banner-fp": "./scripts/project/banner/index-fp.ts",
        "project-registration-creation": "./scripts/project/registration/index-for-modal.ts",
        "site-admin-generate-pie-charts": "./scripts/site-admin/generate-pie-charts.js",
        "site-admin-mass-emailing": "./scripts/site-admin/massmail.js",
        "site-admin-most-recent-logins": "./scripts/site-admin/most-recent-logins.js",
        "site-admin-pending-users": "./scripts/site-admin/pending-users.js",
        "site-admin-permission-delegation": "./scripts/site-admin/permission-delegation.js",
        "site-admin-project-configuration": "./scripts/site-admin/project-configuration.js",
        "site-admin-project-history": "./scripts/site-admin/project-history.js",
        "site-admin-project-list": "./scripts/site-admin/project-list.js",
        "site-admin-project-widgets": "./scripts/site-admin/project-widgets-configuration/index.ts",
        "site-admin-system-events": "./scripts/site-admin/system-events.js",
        "site-admin-system-events-admin-homepage":
            "./scripts/site-admin/system-events-admin-homepage.js",
        "site-admin-system-events-notifications":
            "./scripts/site-admin/system-events-notifications.js",
        "site-admin-trackers-pending-removal": "./scripts/site-admin/trackers-pending-removal.js",
        "site-admin-user-details": "./scripts/site-admin/userdetails.js",
        "trovecat-admin": "./scripts/tuleap/trovecat.js",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
        tuleap: "tuleap",
        ckeditor: "CKEDITOR",
        jquery: "jQuery",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files,
            webpack_configurator.rule_mustache_files,
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getTypescriptCheckerPlugin(false),
        webpack_configurator.getMomentLocalePlugin(),
    ],
    resolve: {
        extensions: [".ts", ".js"],
    },
};

const webpack_config_for_vue = {
    entry: {
        "frs-permissions": "./scripts/frs/permissions-per-group/index.js",
        "news-permissions": "./scripts/news/permissions-per-group/index.js",
        "project-admin-banner": "./scripts/project/admin/banner/index-banner-project-admin.ts",
        "project-admin-services": "./scripts/project/admin/services/src/index-project-admin.js",
        "project-registration": "./scripts/project/registration/index.ts",
        "site-admin-services": "./scripts/project/admin/services/src/index-site-admin.js",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
        ckeditor: "CKEDITOR",
        jquery: "jQuery",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader,
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getVueLoaderPlugin(),
        webpack_configurator.getTypescriptCheckerPlugin(true),
    ],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias,
    },
    resolve: {
        extensions: [".js", ".ts", ".vue"],
    },
};

const fat_combined_files = [
        "./www/scripts/prototype/prototype.js",
        "./www/scripts/protocheck/protocheck.js",
        "./www/scripts/scriptaculous/scriptaculous.js",
        "./www/scripts/scriptaculous/builder.js",
        "./www/scripts/scriptaculous/effects.js",
        "./www/scripts/scriptaculous/dragdrop.js",
        "./www/scripts/scriptaculous/controls.js",
        "./www/scripts/scriptaculous/slider.js",
        "./www/scripts/jquery/jquery-1.9.1.min.js",
        "./www/scripts/jquery/jquery-ui.min.js",
        "./www/scripts/jquery/jquery-noconflict.js",
        "./www/scripts/tuleap/project-history.js",
        "./www/scripts/bootstrap/bootstrap-dropdown.js",
        "./www/scripts/bootstrap/bootstrap-button.js",
        "./www/scripts/bootstrap/bootstrap-modal.js",
        "./www/scripts/bootstrap/bootstrap-collapse.js",
        "./www/scripts/bootstrap/bootstrap-tooltip.js",
        "./www/scripts/bootstrap/bootstrap-tooltip-fix-prototypejs-conflict.js",
        "./www/scripts/bootstrap/bootstrap-popover.js",
        "./www/scripts/bootstrap/bootstrap-select/bootstrap-select.js",
        "./www/scripts/bootstrap/bootstrap-tour/bootstrap-tour.min.js",
        "./www/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js",
        "./www/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker.fr.js",
        "./www/scripts/bootstrap/bootstrap-datetimepicker/js/bootstrap-datetimepicker-fix-prototypejs-conflict.js",
        "./www/scripts/jscrollpane/jquery.mousewheel.js",
        "./www/scripts/jscrollpane/jquery.jscrollpane.min.js",
        "./www/scripts/select2/select2.min.js",
        "./www/scripts/vendor/at/js/caret.min.js",
        "./www/scripts/vendor/at/js/atwho.min.js",
        "./www/scripts/viewportchecker/viewport-checker.js",
        "./www/scripts/clamp.js",
        "./www/scripts/codendi/common.js",
        "./www/scripts/tuleap/massmail_initialize_ckeditor.js",
        "./www/scripts/tuleap/get-style-class-property.js",
        "./scripts/tuleap/listFilter.js",
        "./www/scripts/codendi/feedback.js",
        "./www/scripts/codendi/CreateProject.js",
        "./www/scripts/codendi/cross_references.js",
        "./scripts/codendi/Tooltip.js",
        "./www/scripts/codendi/Tooltip-loader.js",
        "./www/scripts/codendi/Toggler.js",
        "./www/scripts/codendi/DropDownPanel.js",
        "./www/scripts/autocomplete.js",
        "./www/scripts/textboxlist/multiselect.js",
        "./www/scripts/tablekit/tablekit.js",
        "./www/scripts/lytebox/lytebox.js",
        "./www/scripts/lightwindow/lightwindow.js",
        "./scripts/tuleap/escaper.js",
        "./www/scripts/codendi/Tracker.js",
        "./www/scripts/codendi/TreeNode.js",
        "./www/scripts/tuleap/tuleap-modal.js",
        "./www/scripts/tuleap/tuleap-tours.js",
        "./www/scripts/tuleap/tuleap-standard-homepage.js",
        "./www/scripts/tuleap/datetimepicker.js",
        "./www/scripts/tuleap/svn.js",
        "./www/scripts/tuleap/search.js",
        "./www/scripts/tuleap/tuleap-mention.js",
        "./www/scripts/tuleap/project-privacy-tooltip.js",
        "./www/scripts/tuleap/massmail_project_members.js",
        "./www/scripts/tuleap/tuleap-ckeditor-toolbar.js",
    ],
    subset_combined_files = [
        "./www/scripts/jquery/jquery-2.1.1.min.js",
        "./www/scripts/bootstrap/bootstrap-tooltip.js",
        "./www/scripts/bootstrap/bootstrap-popover.js",
        "./www/scripts/bootstrap/bootstrap-button.js",
        "./www/scripts/tuleap/project-privacy-tooltip.js",
    ],
    subset_combined_flamingparrot_files = [
        "./www/scripts/bootstrap/bootstrap-dropdown.js",
        "./www/scripts/bootstrap/bootstrap-modal.js",
        "./www/scripts/bootstrap/bootstrap-tour/bootstrap-tour.min.js",
        "./www/scripts/jscrollpane/jquery.mousewheel.js",
        "./www/scripts/jscrollpane/jquery.jscrollpane.min.js",
        "./www/scripts/tuleap/tuleap-tours.js",
        "./scripts/tuleap/listFilter.js",
        "./scripts/codendi/Tooltip.js",
    ];

const webpack_config_legacy_combined = {
    entry: {
        null: "null_entry",
    },
    context,
    output,
    plugins: [
        ...webpack_configurator.getLegacyConcatenatedScriptsPlugins({
            "tuleap.js": fat_combined_files,
            "tuleap_subset.js": subset_combined_files,
            "tuleap_subset_flamingparrot.js": subset_combined_files.concat(
                subset_combined_flamingparrot_files
            ),
        }),
        manifest_plugin,
    ],
};

module.exports = [
    webpack_config_for_ckeditor,
    webpack_config_legacy_combined,
    webpack_config_for_rich_text_editor,
    webpack_config_for_flaming_parrot_code,
    webpack_config_for_burning_parrot_code,
    webpack_config_for_vue,
];