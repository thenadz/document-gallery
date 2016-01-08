/**
 * Cause gallery shortcode is hardcoded into WP core and has a lot of relations with Media Manager and other parts
 * had to copy related code and adjust to the DG needs.
 * Since WP core make use of shortcode tag to get (build) needed function name while handling shortcode (especially in visual editor),
 * named all DG related variables and functions using prefix or suffix (depending on context) same as shortcode tag - dg
 * As long as WP core has strange conditional statements for order and orderby attributes had to rename them by adding dg prefix.
 *
 */
( function ($, _) {
    // Check if environment is suitable for the code to be executed
    if ( typeof wp.media.collection !== 'function' ) return;

    var l10n,
        media = wp.media;

    // Link any localized strings.
    l10n = media.view.l10n = typeof _wpMediaViewsL10n === 'undefined' ? {} : _wpMediaViewsL10n;
    jQuery.extend(l10n, DGl10n);

    // Based on code from /wp-includes/js/media-views.js WP 4.1
    /**
     * wp.media.controller.dgEdit
     *
     * A state for editing a dg's images and settings.
     *
     * @class
     * @augments wp.media.controller.Library
     * @augments wp.media.controller.State
     * @augments Backbone.Model
     *
     * @param {object}                     [attributes]                       The attributes hash passed to the state.
     * @param {string}                     [attributes.id=dg-edit]       Unique identifier.
     * @param {string}                     [attributes.title=Edit Document Gallery]    Title for the state. Displays in the frame's title region.
     * @param {wp.media.model.Attachments} [attributes.library]               The collection of attachments in the dg.
     *                                                                        If one is not supplied, an empty media.model.Selection collection is created.
     * @param {boolean}                    [attributes.multiple=false]        Whether multi-select is enabled.
     * @param {boolean}                    [attributes.searchable=false]      Whether the library is searchable.
     * @param {boolean}                    [attributes.sortable=true]         Whether the Attachments should be sortable. Depends on the orderby property being set to menuOrder on the attachments collection.
     * @param {string|false}               [attributes.content=browse]        Initial mode for the content region.
     * @param {string|false}               [attributes.toolbar=image-details] Initial mode for the toolbar region.
     * @param {boolean}                    [attributes.describe=true]         Whether to offer UI to describe attachments - e.g. captioning images in a dg.
     * @param {boolean}                    [attributes.displaySettings=true]  Whether to show the attachment display settings interface.
     * @param {boolean}                    [attributes.dragInfo=true]         Whether to show instructional text about the attachments being sortable.
     * @param {int}                        [attributes.idealColumnWidth=170]  The ideal column width in pixels for attachments.
     * @param {boolean}                    [attributes.editing=false]         Whether the dg is being created, or editing an existing instance.
     * @param {int}                        [attributes.priority=60]           The priority for the state link in the media menu.
     * @param {boolean}                    [attributes.syncSelection=false]   Whether the Attachments selection should be persisted from the last state.
     *                                                                        Defaults to false for this state, because the library passed in  *is* the selection.
     * @param {view}                       [attributes.AttachmentView]        The single `Attachment` view to be used in the `Attachments`.
     *                                                                        If none supplied, defaults to wp.media.view.Attachment.EditLibrary.
     */
    media.controller.dgEdit = media.controller.Library.extend({
        defaults: {
            id: 'dg-edit',
            title: l10n.editdgTitle,
            multiple: false,
            searchable: false,
            sortable: true,
            display: false,
            content: 'browse',
            toolbar: 'dg-edit',
            describe: true,
            displaySettings: true,
            dragInfo: true,
            idealColumnWidth: 170,
            editing: false,
            priority: 60,
            syncSelection: false
        },

        /**
         * @since 3.5.0
         */
        initialize: function () {
            // If we haven't been provided a `library`, create a `Selection`.
            if (!this.get('library'))
                this.set('library', new media.model.Selection());

            // The single `Attachment` view to be used in the `Attachments` view.
            if (!this.get('AttachmentView'))
                this.set('AttachmentView', media.view.Attachment.EditLibrary);
            media.controller.Library.prototype.initialize.apply(this, arguments);
        },

        /**
         * @since 3.5.0
         */
        activate: function () {
            var library = this.get('library');

            // Limit the library to images only.
            //library.props.set( 'type', 'image' );

            // Watch for uploaded attachments.
            this.get('library').observe(wp.Uploader.queue);

            this.frame.on('content:render:browse', this.dgSettings, this);

            media.controller.Library.prototype.activate.apply(this, arguments);
        },

        /**
         * @since 3.5.0
         */
        deactivate: function () {
            // Stop watching for uploaded attachments.
            this.get('library').unobserve(wp.Uploader.queue);

            this.frame.off('content:render:browse', this.dgSettings, this);

            media.controller.Library.prototype.deactivate.apply(this, arguments);
        },

        /**
         * @since 3.5.0
         *
         * @param browser
         */
        dgSettings: function (browser) {
            if (!this.get('displaySettings')) {
                return;
            }

            var library = this.get('library');

            if (!library || !browser) {
                return;
            }

            library.dg = library.dg || new Backbone.Model();

            browser.sidebar.set({
                dg: new media.view.Settings.dg({
                    controller: this,
                    model: library.dg,
                    priority: 40
                })
            });

            browser.toolbar.set('reverse', {
                text: l10n.reverseOrder,
                priority: 80,

                click: function () {
                    library.reset(library.toArray().reverse());
                }
            });
        }
    });

    /**
     * A state for selecting more images to add to a dg.
     *
     * @class
     * @augments wp.media.controller.Library
     * @augments wp.media.controller.State
     * @augments Backbone.Model
     *
     * @param {object}                     [attributes]                         The attributes hash passed to the state.
     * @param {string}                     [attributes.id=dg-library]      Unique identifier.
     * @param {string}                     [attributes.title=Add to Document Gallery]    Title for the state. Displays in the frame's title region.
     * @param {boolean}                    [attributes.multiple=add]            Whether multi-select is enabled. @todo 'add' doesn't seem do anything special, and gets used as a boolean.
     * @param {wp.media.model.Attachments} [attributes.library]                 The attachments collection to browse.
     *                                                                          If one is not supplied, a collection of all images will be created.
     * @param {boolean|string}             [attributes.filterable=uploaded]     Whether the library is filterable, and if so what filters should be shown.
     *                                                                          Accepts 'all', 'uploaded', or 'unattached'.
     * @param {string}                     [attributes.menu=dg]            Initial mode for the menu region.
     * @param {string}                     [attributes.content=upload]          Initial mode for the content region.
     *                                                                          Overridden by persistent user setting if 'contentUserSetting' is true.
     * @param {string}                     [attributes.router=browse]           Initial mode for the router region.
     * @param {string}                     [attributes.toolbar=dg-add]     Initial mode for the toolbar region.
     * @param {boolean}                    [attributes.searchable=true]         Whether the library is searchable.
     * @param {boolean}                    [attributes.sortable=true]           Whether the Attachments should be sortable. Depends on the orderby property being set to menuOrder on the attachments collection.
     * @param {boolean}                    [attributes.autoSelect=true]         Whether an uploaded attachment should be automatically added to the selection.
     * @param {boolean}                    [attributes.contentUserSetting=true] Whether the content region's mode should be set and persisted per user.
     * @param {int}                        [attributes.priority=100]            The priority for the state link in the media menu.
     * @param {boolean}                    [attributes.syncSelection=false]     Whether the Attachments selection should be persisted from the last state.
     *                                                                          Defaults to false because for this state, because the library of the Edit Document Gallery state is the selection.
     */
    media.controller.dgAdd = media.controller.Library.extend({
        defaults: _.defaults({
            id: 'dg-library',
            title: l10n.addTodgTitle,
            multiple: 'add',
            filterable: 'uploaded',
            menu: 'dg',
            toolbar: 'dg-add',
            priority: 100,
            syncSelection: false
        }, media.controller.Library.prototype.defaults),

        /**
         * @since 3.5.0
         */
        initialize: function () {
            // If a library wasn't supplied, create a library of images.
            if (!this.get('library'))
                this.set('library', media.query());

            media.controller.Library.prototype.initialize.apply(this, arguments);
        },

        /**
         * @since 3.5.0
         */
        activate: function () {
            var library = this.get('library'),
                edit = this.frame.state('dg-edit').get('library');

            if (this.editLibrary && this.editLibrary !== edit)
                library.unobserve(this.editLibrary);

            // Accepts attachments that exist in the original library and
            // that do not exist in dg's library.
            library.validator = function (attachment) {
                return !!this.mirroring.get(attachment.cid) && !edit.get(attachment.cid) && media.model.Selection.prototype.validator.apply(this, arguments);
            };

            // Reset the library to ensure that all attachments are re-added
            // to the collection. Do so silently, as calling `observe` will
            // trigger the `reset` event.
            library.reset(library.mirroring.models, {silent: true});
            library.observe(edit);
            this.editLibrary = edit;

            media.controller.Library.prototype.activate.apply(this, arguments);
        }
    });

    /**
     * wp.media.view.Settings.dg
     *
     * @class
     * @augments wp.media.view.Settings
     * @augments wp.media.View
     * @augments wp.Backbone.View
     * @augments Backbone.View
     */
    media.view.Settings.dg = media.view.Settings.extend({
        update: function( key ) {
            var value = this.model.get( key ),
                $setting = this.$('[data-setting="' + key + '"]'),
                $buttons, $value;

            // Bail if we didn't find a matching setting.
            if ( ! $setting.length ) {
                return;
            }

            // Attempt to determine how the setting is rendered and update
            // the selected value.

            // Handle dropdowns.
            if ( $setting.is('select') ) {
                $value = $setting.find('[value="' + value + '"]');

                if ( $value.length ) {
                    $setting.find('option').prop( 'selected', false );
                    $value.prop( 'selected', true );
                } else {
                    // If we can't find the desired value, record what *is* selected.
                    this.model.set( key, $setting.find(':selected').val() );
                }

                // Handle button groups.
            } else if ( $setting.hasClass('button-group') ) {
                $buttons = $setting.find('button').removeClass('active');
                $buttons.filter( '[value="' + value + '"]' ).addClass('active');

                // Handle text inputs and textareas.
            } else if ( $setting.is('input[type="text"], input[type="number"], textarea') ) {
                if ( ! $setting.is(':focus') ) {
                    $setting.val( value );
                }
                // Handle checkboxes.
            } else if ( $setting.is('input[type="checkbox"]') ) {
                $setting.prop( 'checked', !! value && 'false' !== value );
            }
        },
        className: 'collection-settings dg-settings',
        template: media.template('dg-settings')
    });

    // supersede the default MediaFrame.Post view
    // hint from https://gist.github.com/Fab1en/4586865
    var wpMediaFramePost = wp.media.view.MediaFrame.Post;
    wp.media.view.MediaFrame.Post = wpMediaFramePost.extend(
        {
            initialize: function () {
                wpMediaFramePost.prototype.initialize.apply(this, arguments);
                this.states.add([
                    new media.controller.Library({
                        id: 'dg',
                        title: l10n.dgMenuTitle,
                        priority: 50,
                        toolbar: 'main-dg',
                        filterable: 'all',
                        multiple: 'add',
                        editable: false,

                        library: media.query(this.options.library)
                    }),

                    // Document Gallery states.
                    new media.controller.dgEdit({
                        library: this.options.selection,
                        editing: this.options.editing,
                        menu: 'dg'
                    }),

                    new media.controller.dgAdd()
                ]);

                this.on('menu:create:dg', this.createMenu, this);
                this.on('toolbar:create:main-dg', this.createToolbar, this);

                this.on('menu:render:dg', this.dgMenu, this);
                this.on('toolbar:render:main-dg', this.maindgToolbar, this);
                this.on('toolbar:render:dg-edit', this.dgEditToolbar, this);
                this.on('toolbar:render:dg-add', this.dgAddToolbar, this);
            },

            /**
             * @param {wp.Backbone.View} view
             */
            dgMenu: function (view) {
                var lastState = this.lastState(),
                    previous = lastState && lastState.id,
                    frame = this;

                view.set({
                    cancel: {
                        text: l10n.canceldgTitle,
                        priority: 20,
                        click: function () {
                            if (previous) {
                                frame.setState(previous);
                            } else {
                                frame.close();
                            }

                            // Keep focus inside media modal
                            // after canceling a dg
                            this.controller.modal.focusManager.focus();
                        }
                    },
                    separateCancel: new media.View({
                        className: 'separator',
                        priority: 40
                    })
                });
            },

            /**
             * @param {wp.Backbone.View} view
             */
            maindgToolbar: function (view) {
                var controller = this;

                this.selectionStatusToolbar(view);

                view.set('dg', {
                    style: 'primary',
                    text: l10n.dgButton,
                    priority: 60,
                    requires: {selection: true},

                    click: function () {
                        var selection = controller.state().get('selection'),
                            edit = controller.state('dg-edit'),
                            models = selection.models;

                        edit.set('library', new media.model.Selection(models, {
                            props: selection.props.toJSON(),
                            multiple: true
                        }));

                        this.controller.setState('dg-edit');

                        // Keep focus inside media modal
                        // after jumping to dg view
                        this.controller.modal.focusManager.focus();
                    }
                });
            },

            dgEditToolbar: function () {
                var editing = this.state().get('editing');
                this.toolbar.set(new media.view.Toolbar({
                    controller: this,
                    items: {
                        insert: {
                            style: 'primary',
                            text: editing ? l10n.updatedg : l10n.insertdg,
                            priority: 80,
                            requires: {library: true},

                            /**
                             * @fires wp.media.controller.State#update
                             */
                            click: function () {
                                var controller = this.controller,
                                    state = controller.state();

                                controller.close();
                                //state.trigger( 'update', state.get('library') ); // calling for workflow.state update, so just execute its contents
                                wp.media.editor.insert(wp.media.dg.shortcode(state.get('library')).string().replace(/\sdgorder=/ig, ' order=').replace(/\sdgorderby=/ig, ' orderby='));

                                // Restore and reset the default state.
                                controller.setState(controller.options.state);
                                controller.reset();
                                if (typeof tinyMCE != 'undefined') {
                                    tinyMCE.activeEditor.fire('dgUpdate');
                                }
                            }
                        }
                    }
                }));
            },

            dgAddToolbar: function () {
                this.toolbar.set(new media.view.Toolbar({
                    controller: this,
                    items: {
                        insert: {
                            style: 'primary',
                            text: l10n.addTodg,
                            priority: 80,
                            requires: {selection: true},

                            /**
                             * @fires wp.media.controller.State#reset
                             */
                            click: function () {
                                var controller = this.controller,
                                    state = controller.state(),
                                    edit = controller.state('dg-edit');

                                edit.get('library').add(state.get('selection').models);
                                state.trigger('reset');
                                controller.setState('dg-edit');
                            }
                        }
                    }
                }));
            }
        });

    // Based on code from /wp-includes/js/media-editor.js WP 4.1

    wp.media._dgDefaults = {
        id: wp.media.view.settings.post && wp.media.view.settings.post.id,
        columns: dgDefaults.columns,
        fancy: dgDefaults.fancy,
        relation: dgDefaults.relation,
        limit: dgDefaults.limit,
        mime_types: dgDefaults.mime_types,
        post_status: dgDefaults.post_status,
        post_type: dgDefaults.post_type,
        attachment_pg: dgDefaults.attachment_pg,
        descriptions: dgDefaults.descriptions,
        new_window: dgDefaults.new_window,
        paginate: dgDefaults.paginate,
        dgorder: dgDefaults.order,
        dgorderby: dgDefaults.orderby
    };

    if (wp.media.view.settings.dgDefaults) {
        wp.media.dgDefaults = _.extend({}, wp.media._dgDefaults, wp.media.view.settings.dgDefaults);
    } else {
        wp.media.dgDefaults = wp.media._dgDefaults;
    }

    wp.media.dg = new wp.media.collection({
        tag: 'dg',
        //type : 'image',
        editTitle: wp.media.view.l10n.editdgTitle,
        defaults: wp.media.dgDefaults,

        setDefaults: function (attrs) {
            var self = this, changed = !_.isEqual(wp.media.dgDefaults, wp.media._dgDefaults);
            _.each(this.defaults, function (value, key) {
                attrs[key] = self.coerce(attrs, key);
                if (value === attrs[key] && ( !changed || value === wp.media._dgDefaults[key] )) {
                    delete attrs[key];
                }
            });
            return attrs;
        }
    });
}(jQuery, _));

// Check if environment is suitable for the code to be executed
if ( typeof window.wp.mce !== 'undefined' && typeof window.wp.mce.views !== 'undefined' ) {
    // Based on code from /wp-includes/js/mce-view.js WP 4.2
    /*
     * The WordPress core TinyMCE views.
     * View for the dg shortcode.
     */
    (function (window, views, media, $) {
        var dg;

        base = {
            state: [],

            edit: function (text, update) {
                // currently the shortcode *must* include ids attribute and may include any of the listed attributes to be editable
                if (text.search(/\sids\s*=/gi) == -1 || text.search(/\s(?!(?:ids|attachment_pg|columns|new_window|descriptions|fancy|orderby|order|paginate|limit)\s*=)[\w\-]+\s*=/gi) > -1) {
                    tinyMCE.activeEditor.windowManager.alert(DGl10n.unfitSCalert);
                } else {
                    var type = this.type,
                        frame = media[type].edit(text.replace(/\sorder\s*=/ig, ' dgorder=').replace(/\sorderby\s*=/ig, ' dgorderby='));

                    this.pausePlayers && this.pausePlayers();

                    _.each(this.state, function (state) {
                        frame.state(state).on('update', function (selection) {
                            update(media[type].shortcode(selection).string(), type === 'dg');
                        });
                    });

                    frame.on('close', function () {
                        frame.detach();
                    });

                    frame.open();
                }
            }
        };

        dg = _.extend({}, base, {
            state: ['dg-edit'],
            template: media.template('editor-dg'),

            initialize: function () {
                var attachments = media.dg.attachments(this.shortcode, media.view.settings.post.id),
                    attrs = this.shortcode.attrs.named,
                    sc = this.text,
                    atts = {},
                    self = this;

                for (prop in attrs) {
                    if (sc.indexOf(' ' + prop + '=') > -1) {
                        atts[prop] = attrs[prop];
                    }
                }
                if (sc.indexOf(' dgorderby=') > -1) {
                    atts['orderby'] = attrs['dgorderby'];
                }
                if (sc.indexOf(' dgorder=') > -1) {
                    atts['order'] = attrs['dgorder'];
                }
                self.render('<div data-shortcode="' +
                    encodeURIComponent(JSON.stringify(atts)) +
                    '"><div class="loading-placeholder"><div class="dashicons dashicons-admin-media"></div><div class="wpview-loading"><ins></ins></div></div></div>');
            }
        });

        views.register('dg', _.extend({}, dg));

    })(window, window.wp.mce.views, window.wp.media, window.jQuery);
}