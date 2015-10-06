( function ($, _) {
    var media = wp.media;

    // Link any localized strings.
    l10n = media.view.l10n = typeof _wpMediaViewsL10n === 'undefined' ? {} : _wpMediaViewsL10n;
    jQuery.extend(l10n, DGl10n);

    /**
     * wp.media.controller.DocumentGalleryEdit
     *
     * A state for editing a Document Gallery's images and settings.
     *
     * @class
     * @augments wp.media.controller.Library
     * @augments wp.media.controller.State
     * @augments Backbone.Model
     *
     * @param {object}                     [attributes]                       The attributes hash passed to the state.
     * @param {string}                     [attributes.id=gallery-edit]       Unique identifier.
     * @param {string}                     [attributes.title=Edit Gallery]    Title for the state. Displays in the frame's title region.
     * @param {wp.media.model.Attachments} [attributes.library]               The collection of attachments in the gallery.
     *                                                                        If one is not supplied, an empty media.model.Selection collection is created.
     * @param {boolean}                    [attributes.multiple=false]        Whether multi-select is enabled.
     * @param {boolean}                    [attributes.searchable=false]      Whether the library is searchable.
     * @param {boolean}                    [attributes.sortable=true]         Whether the Attachments should be sortable. Depends on the orderby property being set to menuOrder on the attachments collection.
     * @param {string|false}               [attributes.content=browse]        Initial mode for the content region.
     * @param {string|false}               [attributes.toolbar=image-details] Initial mode for the toolbar region.
     * @param {boolean}                    [attributes.describe=true]         Whether to offer UI to describe attachments - e.g. captioning images in a gallery.
     * @param {boolean}                    [attributes.displaySettings=true]  Whether to show the attachment display settings interface.
     * @param {boolean}                    [attributes.dragInfo=true]         Whether to show instructional text about the attachments being sortable.
     * @param {int}                        [attributes.idealColumnWidth=170]  The ideal column width in pixels for attachments.
     * @param {boolean}                    [attributes.editing=false]         Whether the gallery is being created, or editing an existing instance.
     * @param {int}                        [attributes.priority=60]           The priority for the state link in the media menu.
     * @param {boolean}                    [attributes.syncSelection=false]   Whether the Attachments selection should be persisted from the last state.
     *                                                                        Defaults to false for this state, because the library passed in  *is* the selection.
     * @param {view}                       [attributes.AttachmentView]        The single `Attachment` view to be used in the `Attachments`.
     *                                                                        If none supplied, defaults to wp.media.view.Attachment.EditLibrary.
     */
    media.controller.DocumentGalleryEdit = media.controller.Library.extend({
        defaults: {
            id: 'document-gallery-edit',
            title: l10n.editDocumentGalleryTitle,
            multiple: false,
            searchable: false,
            sortable: true,
            display: false,
            content: 'browse',
            toolbar: 'document-gallery-edit',
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
            //library.props.set( 'type', '' );

            // Watch for uploaded attachments.
            this.get('library').observe(wp.Uploader.queue);

            this.frame.on('content:render:browse', this.gallerySettings, this);

            media.controller.Library.prototype.activate.apply(this, arguments);
        },

        /**
         * @since 3.5.0
         */
        deactivate: function () {
            // Stop watching for uploaded attachments.
            this.get('library').unobserve(wp.Uploader.queue);

            this.frame.off('content:render:browse', this.gallerySettings, this);

            media.controller.Library.prototype.deactivate.apply(this, arguments);
        },

        /**
         * @since 3.5.0
         *
         * @param browser
         */
        gallerySettings: function (browser) {
            if (!this.get('displaySettings')) {
                return;
            }

            var library = this.get('library');

            if (!library || !browser) {
                return;
            }

            library.gallery = library.gallery || new Backbone.Model();

            browser.sidebar.set({
                document_gallery: new media.view.Settings.DocumentGallery({
                    controller: this,
                    model: library.gallery,
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
     * A state for selecting more images to add to a Document Gallery.
     *
     * @class
     * @augments wp.media.controller.Library
     * @augments wp.media.controller.State
     * @augments Backbone.Model
     *
     * @param {object}                     [attributes]                         The attributes hash passed to the state.
     * @param {string}                     [attributes.id=gallery-library]      Unique identifier.
     * @param {string}                     [attributes.title=Add to Gallery]    Title for the state. Displays in the frame's title region.
     * @param {boolean}                    [attributes.multiple=add]            Whether multi-select is enabled. @todo 'add' doesn't seem do anything special, and gets used as a boolean.
     * @param {wp.media.model.Attachments} [attributes.library]                 The attachments collection to browse.
     *                                                                          If one is not supplied, a collection of all images will be created.
     * @param {boolean|string}             [attributes.filterable=uploaded]     Whether the library is filterable, and if so what filters should be shown.
     *                                                                          Accepts 'all', 'uploaded', or 'unattached'.
     * @param {string}                     [attributes.menu=gallery]            Initial mode for the menu region.
     * @param {string}                     [attributes.content=upload]          Initial mode for the content region.
     *                                                                          Overridden by persistent user setting if 'contentUserSetting' is true.
     * @param {string}                     [attributes.router=browse]           Initial mode for the router region.
     * @param {string}                     [attributes.toolbar=gallery-add]     Initial mode for the toolbar region.
     * @param {boolean}                    [attributes.searchable=true]         Whether the library is searchable.
     * @param {boolean}                    [attributes.sortable=true]           Whether the Attachments should be sortable. Depends on the orderby property being set to menuOrder on the attachments collection.
     * @param {boolean}                    [attributes.autoSelect=true]         Whether an uploaded attachment should be automatically added to the selection.
     * @param {boolean}                    [attributes.contentUserSetting=true] Whether the content region's mode should be set and persisted per user.
     * @param {int}                        [attributes.priority=100]            The priority for the state link in the media menu.
     * @param {boolean}                    [attributes.syncSelection=false]     Whether the Attachments selection should be persisted from the last state.
     *                                                                          Defaults to false because for this state, because the library of the Edit Gallery state is the selection.
     */
    media.controller.DocumentGalleryAdd = media.controller.Library.extend({
        defaults: _.defaults({
            id: 'document-gallery-library',
            title: l10n.addToDocumentGalleryTitle,
            multiple: 'add',
            filterable: 'uploaded',
            menu: 'document-gallery',
            toolbar: 'document-gallery-add',
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
                edit = this.frame.state('document-gallery-edit').get('library');

            if (this.editLibrary && this.editLibrary !== edit)
                library.unobserve(this.editLibrary);

            // Accepts attachments that exist in the original library and
            // that do not exist in gallery's library.
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
     * wp.media.view.Settings.DocumentGallery
     *
     * @class
     * @augments wp.media.view.Settings
     * @augments wp.media.View
     * @augments wp.Backbone.View
     * @augments Backbone.View
     */
    media.view.Settings.DocumentGallery = media.view.Settings.extend({
        className: 'collection-settings gallery-settings document-gallery-settings',
        template: media.template('document-gallery-settings')
    });

    // supersede the default MediaFrame.Post view
    var wpMediaFramePost = wp.media.view.MediaFrame.Post;
    wp.media.view.MediaFrame.Post = wpMediaFramePost.extend(
        {
            initialize: function () {
                wpMediaFramePost.prototype.initialize.apply(this, arguments);
                this.states.add([
                    new media.controller.Library({
                        id: 'document-gallery',
                        title: l10n.documentGalleryMenuTitle,
                        priority: 50,
                        toolbar: 'main-document-gallery',
                        filterable: 'all',
                        multiple: 'add',
                        editable: false,

                        library: media.query(this.options.library)
                    }),

                    // Document Gallery states.
                    new media.controller.DocumentGalleryEdit({
                        library: this.options.selection,
                        editing: this.options.editing,
                        menu: 'document-gallery'
                    }),

                    new media.controller.DocumentGalleryAdd()
                ]);

                this.on('menu:create:document-gallery', this.createMenu, this);
                this.on('toolbar:create:main-document-gallery', this.createToolbar, this);

                this.on('menu:render:document-gallery', this.documentGalleryMenu, this);
                this.on('toolbar:render:main-document-gallery', this.mainDocumentGalleryToolbar, this);
                this.on('toolbar:render:document-gallery-edit', this.documentGalleryEditToolbar, this);
                this.on('toolbar:render:document-gallery-add', this.documentGalleryAddToolbar, this);
            },

            documentGalleryMenu: function (view) {
                var lastState = this.lastState(),
                    previous = lastState && lastState.id,
                    frame = this;

                view.set({
                    cancel: {
                        text: l10n.cancelDocumentGalleryTitle,
                        priority: 20,
                        click: function () {
                            if (previous) {
                                frame.setState(previous);
                            } else {
                                frame.close();
                            }

                            // Keep focus inside media modal
                            // after canceling a gallery
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
            mainDocumentGalleryToolbar: function (view) {
                var controller = this;

                this.selectionStatusToolbar(view);

                view.set('document-gallery', {
                    style: 'primary',
                    text: l10n.documentGalleryButton,
                    priority: 60,
                    requires: {selection: true},

                    click: function () {
                        var selection = controller.state().get('selection'),
                            edit = controller.state('document-gallery-edit'),
                            models = selection.models;

                        edit.set('library', selection);

                        this.controller.setState('document-gallery-edit');

                        // Keep focus inside media modal
                        // after jumping to gallery view
                        this.controller.modal.focusManager.focus();
                    }
                });
            },

            documentGalleryEditToolbar: function () {
                var editing = this.state().get('editing');
                this.toolbar.set(new media.view.Toolbar({
                    controller: this,
                    items: {
                        insert: {
                            style: 'primary',
                            text: editing ? l10n.updateDocumentGallery : l10n.insertDocumentGallery,
                            priority: 80,
                            requires: {library: true},

                            /**
                             * @fires wp.media.controller.State#update
                             */
                            click: function () {
                                var controller = this.controller,
                                    state = controller.state();

                                controller.close();
                                //state.trigger( 'update', state.get('library') );
                                wp.media.editor.insert(wp.media.gallery.shortcode(state.get('library')).string().replace(/^\[gallery/ig, '[dg').replace(/DGorderby/ig, 'orderby'));

                                // Restore and reset the default state.
                                controller.setState(controller.options.state);
                                controller.reset();
                            }
                        }
                    }
                }));
            },

            documentGalleryAddToolbar: function () {
                this.toolbar.set(new media.view.Toolbar({
                    controller: this,
                    items: {
                        insert: {
                            style: 'primary',
                            text: l10n.addToDocumentGallery,
                            priority: 80,
                            requires: {selection: true},

                            /**
                             * @fires wp.media.controller.State#reset
                             */
                            click: function () {
                                var controller = this.controller,
                                    state = controller.state(),
                                    edit = controller.state('document-gallery-edit');

                                edit.get('library').add(state.get('selection').models);
                                state.trigger('reset');
                                controller.setState('document-gallery-edit');
                            }
                        }
                    }
                }));
            }
        });
}(jQuery, _));