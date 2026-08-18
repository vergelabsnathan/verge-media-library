/*
 *  Media view extensions.
 *
 *  Recovered from eml-media-views.min.js, which upstream shipped minified with
 *  no readable counterpart. Formatting was restored with Prettier and the
 *  mangled outer-scope identifiers were renamed through a scope-aware pass
 *  (espree + eslint-scope), so only bindings belonging to the outer closure
 *  moved and shadowed names inside callbacks were left alone. No behaviour was
 *  changed in the recovery itself.
 *
 *  Structure worth knowing before editing: this file patches core wp.media
 *  views in three different ways, and they are not equally safe.
 *
 *    core.<Name>.<method>.apply(this, arguments)   wraps core, safe
 *    media.view.X = media.view.Y.extend({...})     adds a new view, safe
 *    _.extend(media.view.X.prototype, {...})       may REPLACE core outright
 *
 *  The replacements are the liability: they discard core's implementation, so
 *  they silently fall behind whenever WordPress changes it. That is what broke
 *  the toolbar in WordPress 7.0, and why createToolbar below no longer renders
 *  core's filters-heading. Prefer wrapping. See docs/architecture.md.
 */

((window.wp = window.wp || {}),
    (window.vergeml = window.vergeml || { l10n: {} }),
    (function ($, _) {
        var media = wp.media,
            l10n = media.view.l10n,
            mediaTrash = media.view.settings.mediaTrash,
            core = {},
            allFilters = {};
        (_.extend(vergeml.l10n, vergeml_mvln),
            _.defaults(vergeml.l10n, { media_orderby: 'date', media_order: 'DESC' }),
            (core.controllerLibrary = { activate: media.controller.Library.prototype.activate }),
            _.extend(media.controller.Library.prototype, {
                activate: function () {
                    (core.controllerLibrary.activate.apply(this, arguments),
                        wp.Uploader.queue.on('add', this.beforeUpload, this),
                        wp.Uploader.queue.on('reset', this.afterUpload, this));
                },
                beforeUpload: function () {
                    1 == wp.Uploader.queue.length &&
                        $('.attachment-filters:has(option[value!="all"]:selected)')
                            .val('all')
                            .change();
                },
                afterUpload: function () {
                    var e = this.get('library'),
                        t = this.get('selection');
                    ('menuOrder' === e.props.get('orderby') && e.saveMenuOrder(),
                        e.reset(e.models),
                        t.trigger('selection:unsingle', t.model, t),
                        t.trigger('selection:single', t.model, t));
                },
                uploading: function (e) {
                    var t = this.frame.content,
                        i = this.get('selection');
                    ('upload' === t.mode() && this.frame.content.mode('browse'),
                        this.get('autoSelect') &&
                            (1 == wp.Uploader.queue.length && i.length && i.reset(),
                            i.add(e),
                            i.trigger('selection:unsingle', i.model, i),
                            i.trigger('selection:single', i.model, i)));
                },
            }),
            _.extend(media.controller.Library.prototype.defaults, {
                idealColumnWidth: vergeml.l10n.ideal_column_width || 170,
            }),
            _.extend(media.view.Attachments.prototype.defaults, {
                idealColumnWidth:
                    $(window).width() < 640 ? 135 : vergeml.l10n.ideal_column_width || 150,
            }),
            (allFilters = { 'click input[type=checkbox]': 'preSave' }),
            _.extend(allFilters, media.view.AttachmentCompat.prototype.events),
            (core.AttachmentCompat = { postSave: media.view.AttachmentCompat.prototype.postSave }),
            _.extend(media.view.AttachmentCompat.prototype, {
                events: allFilters,
                preSave: function () {
                    var t,
                        r = $('input[type=checkbox]', this.$el);
                    (this.controller.isModeActive('eml-grid') &&
                        (t = this.controller.browserView.toolbar.get('spinner')),
                        r.prop('readonly', !0),
                        t && t.show(),
                        (this.noRender = !0),
                        (this.rendered = !1),
                        media.model.Query.cleanQueries());
                },
                postSave: function (t) {
                    var i,
                        r,
                        l,
                        s,
                        n = $('input[type=checkbox]', this.$el);
                    (core.AttachmentCompat.postSave.apply(this, arguments),
                        this.controller.isModeActive('eml-grid') &&
                            (l = this.controller.browserView.toolbar.get('spinner')),
                        n.prop('readonly', !1),
                        l && l.hide(),
                        'edit-attachment' !== this.controller._state &&
                            (s = this.controller.toolbar.get()),
                        s &&
                            ((r = t ? 'emlAttachmentSuccess' : 'emlAttachmentError'),
                            (i = s.get(r)).$el.fadeIn(200),
                            setTimeout(function () {
                                i.$el.fadeOut(100);
                            }, 800)));
                },
                render: function () {
                    var i = this.model.get('compat'),
                        r = this.$el,
                        l = this.model.get('tcount');
                    if (i && i.item)
                        return (
                            _.each(l, function (t, i) {
                                var r = $('.eml-taxonomy-filters option[value="' + i + '"]'),
                                    l = r.text();
                                ((l = l.replace(/\(.*?\)/, '(' + t + ')')), r.text(l));
                            }),
                            this.noRender
                                ? this
                                : (this.views.detach(),
                                  this.$el.html(i.item),
                                  this.views.render(),
                                  this.controller.isModeActive('select') &&
                                      'edit-attachment' !== this.controller._state &&
                                      ($.each(
                                          vergeml.l10n.compat_taxonomies_to_hide,
                                          function (e, t) {
                                              r.find('.compat-field-' + t).remove();
                                          },
                                      ),
                                      this.$el.find('.compat-attachment-fields tbody').children()
                                          .length ||
                                          this.$el.find('.media-types-required-info').hide()),
                                  $.each(vergeml.l10n.compat_taxonomies, function (e, t) {
                                      (r
                                          .find('.compat-field-' + t + ' .label')
                                          .addClass('eml-tax-label'),
                                          r
                                              .find('.compat-field-' + t + ' .field')
                                              .addClass('eml-tax-field'));
                                  }),
                                  this)
                        );
                },
            }),
            _.extend(media.view.AttachmentFilters.prototype, {
                change: function () {
                    var i = this.filters[this.el.value],
                        r = this.controller.state().get('selection'),
                        o = this.controller.content.get().toolbar.get('resetFilterButton'),
                        s = $('.attachment-filters').length,
                        n = $('.attachment-filters')
                            .map(function () {
                                return this.value;
                            })
                            .get()
                            .filter(function (e) {
                                return 'all' === e;
                            }).length;
                    (i && this.model.set(i.props),
                        i && r && r.length && 1 !== wp.Uploader.queue.length && r.reset(),
                        i &&
                            mediaTrash &&
                            !_.isUndefined(this.controller.toolbar) &&
                            this.controller.toolbar
                                .get()
                                .$('.media-selection')
                                .toggleClass('trash', 'trash' === i.props.status),
                        _.isUndefined(o) || o.model.set('disabled', s === n));
                },
                select: function () {
                    var e = this.model,
                        i = 'all',
                        r = e.toJSON();
                    ((r = _.omit(r, 'orderby', 'order')),
                        _.find(this.filters, function (e, l) {
                            var o = _.omit(e.props, 'orderby', 'order');
                            if (
                                _.all(o, function (e, i) {
                                    return e === (_.isUndefined(r[i]) ? null : r[i]);
                                })
                            )
                                return (i = l);
                        }),
                        this.$el.val(i));
                },
            }),
            (core.AttachmentFilters = {
                All: { createFilters: media.view.AttachmentFilters.All.prototype.createFilters },
                Uploaded: {
                    createFilters: media.view.AttachmentFilters.Uploaded.prototype.createFilters,
                },
            }),
            _.extend(media.view.AttachmentFilters.All.prototype, {
                createFilters: function () {
                    var e,
                        i = _.intersection(
                            _.keys(vergeml.l10n.taxonomies),
                            vergeml.l10n.filter_taxonomies,
                        );
                    (core.AttachmentFilters.All.createFilters.apply(this, arguments),
                        _.each(this.filters, function (e, t) {
                            ((e.props.uncategorized = null),
                                (e.props.orderby = vergeml.l10n.media_orderby),
                                (e.props.order = vergeml.l10n.media_order));
                        }),
                        (this.filters.uncategorized = {
                            text: vergeml.l10n.uncategorized,
                            props: {
                                uploadedTo: null,
                                uncategorized: !0,
                                status: null,
                                type: null,
                                orderby: vergeml.l10n.media_orderby,
                                order: vergeml.l10n.media_order,
                            },
                            priority: 60,
                        }),
                        (e = this.filters.uncategorized.props),
                        _.each(i, function (t) {
                            e[t] = null;
                        }),
                        mediaTrash &&
                            (this.controller.isModeActive('grid') ||
                                this.controller.isModeActive('eml-grid')) &&
                            (this.filters.trash = {
                                text: l10n.trash,
                                props: {
                                    uploadedTo: null,
                                    status: 'trash',
                                    type: null,
                                    orderby: 'date',
                                    order: 'DESC',
                                },
                                priority: 70,
                            }));
                },
            }),
            _.extend(media.view.AttachmentFilters.Uploaded.prototype, {
                createFilters: function () {
                    (core.AttachmentFilters.Uploaded.createFilters.apply(this, arguments),
                        _.each(this.filters, function (e, t) {
                            ((e.props.orderby = vergeml.l10n.media_orderby),
                                (e.props.order = vergeml.l10n.media_order));
                        }));
                },
            }),
            (media.view.AttachmentFilters.Taxonomy = media.view.AttachmentFilters.extend({
                id: function () {
                    return 'media-attachment-' + this.options.taxonomy + '-filters';
                },
                className: function () {
                    return (
                        'attachment-filters eml-taxonomy-filters attachment-' +
                        this.options.taxonomy +
                        '-filter'
                    );
                },
                createFilters: function () {
                    var i = {},
                        r = this;
                    (_.each(r.options.termList || {}, function (t, l) {
                        var o = t.term_id,
                            s = $('<div/>').html(t.term_row).text();
                        ((i[o] = {
                            text: s,
                            props: {
                                uncategorized: null,
                                orderby: vergeml.l10n.media_orderby,
                                order: vergeml.l10n.media_order,
                            },
                            priority: l + 4,
                        }),
                            (i[o].props[r.options.taxonomy] = o));
                    }),
                        (i.all = {
                            text: vergeml.l10n.filter_by + ' ' + r.options.pluralName,
                            props: {
                                uncategorized: null,
                                orderby: vergeml.l10n.media_orderby,
                                order: vergeml.l10n.media_order,
                            },
                            priority: 1,
                        }),
                        (i.all.props[r.options.taxonomy] = null),
                        (i.in = {
                            text:
                                '&#8212; ' +
                                vergeml.l10n.in +
                                ' ' +
                                r.options.pluralName +
                                ' &#8212;',
                            props: {
                                uncategorized: null,
                                orderby: vergeml.l10n.media_orderby,
                                order: vergeml.l10n.media_order,
                            },
                            priority: 2,
                        }),
                        (i.in.props[r.options.taxonomy] = 'in'),
                        (i.not_in = {
                            text:
                                '&#8212; ' +
                                vergeml.l10n.not_in +
                                ' ' +
                                r.options.pluralName +
                                ' &#8212;',
                            props: {
                                uncategorized: null,
                                orderby: vergeml.l10n.media_orderby,
                                order: vergeml.l10n.media_order,
                            },
                            priority: 3,
                        }),
                        (i.not_in.props[r.options.taxonomy] = 'not_in'),
                        (this.filters = i));
                },
            })),
            (media.view.AttachmentFilters.Authors = media.view.AttachmentFilters.extend({
                id: 'author-filter',
                createFilters: function () {
                    var e = {};
                    (_.each(this.options.users || {}, function (t, i) {
                        var r = t.user_id,
                            l = t.user_name;
                        e[r] = {
                            text: l,
                            props: {
                                author: r,
                                orderby: vergeml.l10n.media_orderby,
                                order: vergeml.l10n.media_order,
                            },
                            priority: i + 2,
                        };
                    }),
                        (e.all = {
                            text: vergeml.l10n.in + ' ' + vergeml.l10n.authors,
                            props: {
                                author: null,
                                orderby: vergeml.l10n.media_orderby,
                                order: vergeml.l10n.media_order,
                            },
                            priority: 1,
                        }),
                        (this.filters = e));
                },
            })),
            (media.view.Button.resetFilters = media.view.Button.extend({
                id: 'reset-all-filters',
                initialize: function () {
                    (media.view.Button.prototype.initialize.apply(this, arguments),
                        this.controller.on(
                            'select:activate select:deactivate',
                            this.toogleResetFilters,
                            this,
                        ));
                },
                click: function (t) {
                    ('#' === this.attributes.href && t.preventDefault(),
                        $('.attachment-filters:has(option[value!="all"]:selected)').each(
                            function (t) {
                                $(this).val('all').change();
                            },
                        ));
                },
                toogleResetFilters: function () {
                    this.$el.toggleClass('hidden');
                },
            })),
            (media.view.emlAttachmentDetailsEditMessage = media.View.extend({
                tagName: 'div',
                id: 'eml-save-changes-message',
                initialize: function () {
                    ((this.text = this.options.text), (this.class = this.options.class));
                },
                render: function () {
                    return (
                        this.$el.addClass(this.class),
                        this.$el.html('<p><strong>' + this.text + '</strong></p>'),
                        this
                    );
                },
            })),
            _.extend(media.view.Attachment.Details.prototype, {
                deleteAttachment: function (e) {
                    (e.preventDefault(),
                        window.confirm(l10n.warnDelete) &&
                            (this.model.destroy(),
                            this.controller.modal && this.controller.modal.focusManager.focus()));
                },
            }),
            (core.AttachmentsBrowser = {
                initialize: media.view.AttachmentsBrowser.prototype.initialize,
                createSidebar: media.view.AttachmentsBrowser.prototype.createSidebar,
                createSingle: media.view.AttachmentsBrowser.prototype.createSingle,
                disposeSingle: media.view.AttachmentsBrowser.prototype.disposeSingle,
            }),
            _.extend(media.view.AttachmentsBrowser.prototype, {
                initialize: function () {
                    (core.AttachmentsBrowser.initialize.apply(this, arguments),
                        (this.controller.isModeActive('select') ||
                            this.controller.isModeActive('eml-grid')) &&
                            (this.controller.isModeActive('eml-grid') &&
                                (this.sidebar.$el.width(vergeml.l10n.grid_sidebar_width),
                                this.on('ready', this.fixLayout, this),
                                $(document).on(
                                    'click',
                                    '.notice-dismiss',
                                    _.debounce(_.bind(this.fixLayout, this), 250),
                                )),
                            this.controller.isModeActive('select') &&
                                (this.on('ready', this.fixLayout, this),
                                $(document).on(
                                    'click',
                                    '.acf-expand-details',
                                    _.bind(this.fixLayout, this),
                                ),
                                parseInt(vergeml.l10n.filter_uploaded) &&
                                    'post-php' === window.adminpage &&
                                    ((filters = this.toolbar.get('filters')),
                                    (uploaded = filters.filters.uploaded),
                                    filters.model.set(uploaded.props))),
                            (this.$window = $(window)),
                            this.$window.on('resize', _.debounce(_.bind(this.fixLayout, this), 15)),
                            this.controller.on(
                                'sidebar:on',
                                _.debounce(
                                    _.bind(this.scrollAttachmentIntoView, this, {
                                        block: 'center',
                                    }),
                                    15,
                                ),
                            )));
                },
                fixLayout: function () {
                    if (
                        this.controller.isModeActive('select') ||
                        this.controller.isModeActive('eml-grid')
                    ) {
                        var i = this.attachmentsWrapper || this.attachments,
                            r = this.toolbar,
                            l = $(
                                '.eml-media-css .updated:visible, .eml-media-css .error:visible, .eml-media-css .notice:visible, .eml-media-css .notice-error:visible, .eml-media-css .notice-warning:visible, .eml-media-css .notice-success:visible, .eml-media-css .notice-info:visible',
                            ),
                            o = $('.eml-media-css .update-nag'),
                            s = 0;
                        ((this.sidebarWidth =
                            $(window).width() < 640 ? 0 : parseInt(this.sidebar.$el.outerWidth())),
                            o.length &&
                                (o.css('margin-left', '15px'),
                                this.$el.closest('.wrap').css('top', o.outerHeight() + 25 + 'px')),
                            this.controller.isModeActive('select') &&
                                (i.$el.css('top', r.$el.height() + 10 + 'px'),
                                i.$el.css('right', this.sidebarWidth.toString() + 'px'),
                                this.uploader.$el.css('top', r.$el.height() + 10 + 'px'),
                                this.$el.find('.eml-loader').css('top', r.$el.height() + 10 + 'px'),
                                r.$el
                                    .find('.media-toolbar-secondary')
                                    .prepend(r.$el.find('.instructions'))),
                            this.controller.isModeActive('eml-grid') &&
                                (_.isUndefined(l) ||
                                    l.each(function () {
                                        $(this).hasClass('update-nag') ||
                                            (s += $(this).outerHeight(!0));
                                    }),
                                this.$el.css('top', r.$el.outerHeight() + s + 15 + 'px'),
                                r.$el.css('top', -r.$el.outerHeight() - 25 + 'px'),
                                i.$el.css('top', 0)));
                    }
                },
                scrollAttachmentIntoView: function (t) {
                    var i = this.controller.state().get('selection');
                    1 == i.length &&
                        $(
                            'li.attachment[data-id="' + i.single().get('id') + '"]',
                        )[0].scrollIntoView(t);
                },
                createToolbar: function () {
                    var o,
                        s,
                        n,
                        a = this,
                        c = 1;
                    if (
                        ((n = { controller: this.controller }),
                        (this.controller.isModeActive('grid') ||
                            this.controller.isModeActive('eml-grid')) &&
                            (n.className = 'media-toolbar wp-filter'),
                        (this.toolbar = new media.view.Toolbar(n)),
                        this.views.add(this.toolbar),
                        this.toolbar.set('spinner', new media.view.Spinner({ priority: -40 })),
                        (this.controller.isModeActive('grid') ||
                            this.controller.isModeActive('eml-grid')) &&
                            ((o = media.View.extend({
                                className: 'view-switch media-grid-view-switch',
                                template: media.template('media-library-view-switcher'),
                            })),
                            this.toolbar.set(
                                'libraryViewSwitcher',
                                new o({ controller: this.controller, priority: -90 }).render(),
                            )),
                        (-1 !== $.inArray(this.options.filters, ['uploaded', 'all']) ||
                            (parseInt(vergeml.l10n.force_filters) &&
                                !this.controller.isModeActive('eml-bulk-edit') &&
                                'gallery-edit' !== this.controller._state &&
                                'playlist-edit' !== this.controller._state &&
                                'video-playlist-edit' !== this.controller._state) ||
                            'customize' === vergeml.l10n.current_screen ||
                            'widgets' === vergeml.l10n.current_screen) &&
                            ((-1 === $.inArray('types', vergeml.l10n.filters_to_show) &&
                                this.controller.isModeActive('eml-grid')) ||
                                (this.toolbar.set(
                                    'filtersLabel',
                                    new media.view.Label({
                                        value: l10n.filterByType,
                                        attributes: { for: 'media-attachment-filters' },
                                        priority: -80,
                                    }).render(),
                                ),
                                'uploaded' === this.options.filters
                                    ? this.toolbar.set(
                                          'filters',
                                          new media.view.AttachmentFilters.Uploaded({
                                              controller: this.controller,
                                              model: this.collection.props,
                                              priority: -80,
                                          }).render(),
                                      )
                                    : ((s = new media.view.AttachmentFilters.All({
                                          controller: this.controller,
                                          model: this.collection.props,
                                          priority: -80,
                                      })),
                                      this.toolbar.set('filters', s.render()))),
                            -1 !== $.inArray('dates', vergeml.l10n.filters_to_show) &&
                                media.view.settings.months.length &&
                                (this.toolbar.set(
                                    'dateFilterLabel',
                                    new media.view.Label({
                                        value: l10n.filterByDate,
                                        attributes: { for: 'media-attachment-date-filters' },
                                        priority: -75,
                                    }).render(),
                                ),
                                this.toolbar.set(
                                    'dateFilter',
                                    new media.view.DateFilter({
                                        controller: this.controller,
                                        model: this.collection.props,
                                        priority: -75,
                                    }).render(),
                                )),
                            vergeml.l10n.users.length > 1 &&
                                -1 !== $.inArray('authors', vergeml.l10n.filters_to_show) &&
                                (this.toolbar.set(
                                    'authorFilterLabel',
                                    new media.view.Label({
                                        value: vergeml.l10n.filter_by + ' ' + vergeml.l10n.author,
                                        attributes: { for: 'author-filter' },
                                        priority: c++ - 70,
                                    }).render(),
                                ),
                                this.toolbar.set(
                                    'author-filter',
                                    new media.view.AttachmentFilters.Authors({
                                        controller: this.controller,
                                        model: this.collection.props,
                                        priority: c++ - 70,
                                        users: vergeml.l10n.users,
                                    }).render(),
                                )),
                            -1 !== $.inArray('taxonomies', vergeml.l10n.filters_to_show) &&
                                $.each(vergeml.l10n.taxonomies, function (e, r) {
                                    -1 !== _.indexOf(vergeml.l10n.filter_taxonomies, e) &&
                                        r.term_list.length &&
                                        (a.toolbar.set(
                                            e + 'FilterLabel',
                                            new media.view.Label({
                                                value: vergeml.l10n.filter_by + ' ' + r.plural_name,
                                                attributes: {
                                                    for: 'media-attachment-' + e + '-filters',
                                                },
                                                priority: c++ - 70,
                                            }).render(),
                                        ),
                                        a.toolbar.set(
                                            e + '-filter',
                                            new media.view.AttachmentFilters.Taxonomy({
                                                controller: a.controller,
                                                model: a.collection.props,
                                                priority: c++ - 70,
                                                taxonomy: e,
                                                termList: r.term_list,
                                                singularName: r.singular_name,
                                                pluralName: r.plural_name,
                                            }).render(),
                                        ));
                                }),
                            this.toolbar.$el.find('.attachment-filters').length > 1 &&
                                this.toolbar.set(
                                    'resetFilterButton',
                                    new media.view.Button.resetFilters({
                                        controller: this.controller,
                                        text: vergeml.l10n.reset_filters,
                                        disabled: !0,
                                        priority: c++ - 70,
                                    }).render(),
                                )),
                        this.controller.isModeActive('eml-grid'))
                    ) {
                        var d = this.controller.toolbar.get();
                        ($('body').hasClass('eml-pro-media-css') &&
                            d.set(
                                'emlSelectAllButton',
                                new media.view.emlSelectAllButton({
                                    filters: s,
                                    disabled: !0,
                                    text: vergeml.l10n.select_all,
                                    controller: this.controller,
                                    priority: -80,
                                }).render(),
                            ),
                            d.set(
                                'emlDeselectButton',
                                new media.view.emlDeselectButton({
                                    filters: s,
                                    disabled: !0,
                                    text: vergeml.l10n.deselect,
                                    controller: this.controller,
                                    priority: -70,
                                }).render(),
                            ),
                            d.set(
                                'emlDeleteSelectedButton',
                                new media.view.emlDeleteSelectedButton({
                                    filters: s,
                                    style: 'primary',
                                    disabled: !0,
                                    text: mediaTrash ? l10n.trashSelected : l10n.deletePermanently,
                                    controller: this.controller,
                                    priority: -60,
                                }).render(),
                            ),
                            mediaTrash &&
                                d.set(
                                    'emlDeleteSelectedPermanentlyButton',
                                    new media.view.emlDeleteSelectedPermanentlyButton({
                                        filters: s,
                                        style: 'primary',
                                        disabled: !0,
                                        text: l10n.deletePermanently,
                                        controller: this.controller,
                                        priority: -50,
                                    }).render(),
                                ));
                    }
                    (this.controller.isModeActive('grid') &&
                        (this.toolbar.set(
                            'selectModeToggleButton',
                            new media.view.SelectModeToggleButton({
                                text: l10n.bulkSelect,
                                controller: this.controller,
                                priority: -70,
                            }).render(),
                        ),
                        this.toolbar.set(
                            'deleteSelectedButton',
                            new media.view.DeleteSelectedButton({
                                filters: s,
                                style: 'primary',
                                disabled: !0,
                                text: mediaTrash ? l10n.trashSelected : l10n.deletePermanently,
                                controller: this.controller,
                                priority: -60,
                                click: function () {
                                    var i = [],
                                        o = [],
                                        s = this.controller.state().get('selection'),
                                        n = this.controller.state().get('library');
                                    s.length &&
                                        (mediaTrash || window.confirm(l10n.warnBulkDelete)) &&
                                        ((mediaTrash &&
                                            'trash' !== s.at(0).get('status') &&
                                            !window.confirm(l10n.warnBulkTrash)) ||
                                            (s.each(function (e) {
                                                e.get('nonces').delete
                                                    ? mediaTrash && 'trash' === e.get('status')
                                                        ? (e.set('status', 'inherit'),
                                                          i.push(e.save()),
                                                          o.push(e))
                                                        : mediaTrash
                                                          ? (e.set('status', 'trash'),
                                                            i.push(e.save()),
                                                            o.push(e))
                                                          : e.destroy({ wait: !0 })
                                                    : o.push(e);
                                            }),
                                            i.length
                                                ? (s.remove(o),
                                                  $.when.apply(null, i).then(
                                                      _.bind(function () {
                                                          (n._requery(!0),
                                                              this.controller.trigger(
                                                                  'selection:action:done',
                                                              ));
                                                      }, this),
                                                  ))
                                                : this.controller.trigger(
                                                      'selection:action:done',
                                                  )));
                                },
                            }).render(),
                        ),
                        mediaTrash &&
                            this.toolbar.set(
                                'deleteSelectedPermanentlyButton',
                                new wp.media.view.DeleteSelectedPermanentlyButton({
                                    filters: s,
                                    style: 'primary',
                                    disabled: !0,
                                    text: l10n.deletePermanently,
                                    controller: this.controller,
                                    priority: -55,
                                    click: function () {
                                        var e = [],
                                            t = this.controller.state().get('selection');
                                        t.length &&
                                            window.confirm(l10n.warnBulkDelete) &&
                                            (t.each(function (t) {
                                                t.get('nonces').delete
                                                    ? t.destroy({ wait: !0 })
                                                    : e.push(t);
                                            }),
                                            this.controller.trigger('selection:action:done'));
                                    },
                                }).render(),
                            )),
                    this.options.search &&
                        (this.toolbar.set(
                            'searchLabel',
                            new media.view.Label({
                                value: l10n.searchMediaLabel,
                                attributes: { for: 'media-search-input' },
                                priority: -30,
                            }).render(),
                        ),
                        this.toolbar.set(
                            'search',
                            new media.view.Search({
                                controller: this.controller,
                                model: this.collection.props,
                                priority: -30,
                            }).render(),
                        )),
                    this.options.dragInfo &&
                        this.toolbar.set(
                            'dragInfo',
                            new media.View({
                                el: $('<div class="instructions">' + l10n.dragInfo + '</div>')[0],
                                priority: -40,
                            }),
                        ),
                    'edit-attachment' !== this.controller._state) &&
                        ((d = this.controller.toolbar.get()).set(
                            'emlAttachmentSuccess',
                            new media.view.emlAttachmentDetailsEditMessage({
                                text: vergeml.l10n.saveButton_success,
                                class: 'updated',
                                controller: this.controller,
                                priority: 200,
                            }).render(),
                        ),
                        d.set(
                            'emlAttachmentError',
                            new media.view.emlAttachmentDetailsEditMessage({
                                text: vergeml.l10n.saveButton_failure,
                                class: 'error',
                                controller: this.controller,
                                priority: 220,
                            }).render(),
                        ));
                },
                createSidebar: function () {
                    (core.AttachmentsBrowser.createSidebar.apply(this, arguments),
                        this.controller.isModeActive('eml-grid') && this.toggleSidebar());
                },
                toggleSidebar: function () {
                    var e = this.controller.state().get('selection'),
                        t = this.attachmentsWrapper || this.attachments,
                        i = this.sidebarWidth || 0;
                    e.length
                        ? (this.sidebar.$el.removeClass('hidden'),
                          t.$el.css('right', i.toString() + 'px'),
                          (i += 10),
                          this.uploader.$el.css('right', i.toString() + 'px'),
                          this.controller.trigger('sidebar:on'))
                        : (this.sidebar.$el.addClass('hidden'),
                          t.$el.css('right', 0),
                          this.uploader.$el.css('right', '10px'),
                          this.controller.trigger('sidebar:off'));
                },
                createSingle: function () {
                    if (
                        (core.AttachmentsBrowser.createSingle.apply(this, arguments),
                        this.controller.isModeActive('eml-grid'))
                    ) {
                        var e = this.sidebar,
                            t = this.options.selection.single();
                        ('trash' !== this.options.selection.at(0).get('status') &&
                            e.set(
                                'details',
                                new wp.media.view.emlGridAttachmentDetails({
                                    controller: this.controller,
                                    model: t,
                                    priority: 80,
                                }),
                            ),
                            this.toggleSidebar());
                    }
                },
                disposeSingle: function () {
                    (core.AttachmentsBrowser.disposeSingle.apply(this, arguments),
                        this.controller.isModeActive('eml-grid') && this.toggleSidebar());
                },
                updateContent: function () {
                    var e,
                        t = this;
                    ((e =
                        this.controller.isModeActive('grid') ||
                        this.controller.isModeActive('eml-grid')
                            ? t.attachmentsNoResults
                            : t.uploader),
                        this.collection.length
                            ? (e.$el.addClass('hidden'), t.toolbar.get('spinner').hide())
                            : (this.toolbar.get('spinner').show(),
                              (this.dfd = this.collection.more().done(function () {
                                  (t.collection.length
                                      ? e.$el.addClass('hidden')
                                      : e.$el.removeClass('hidden'),
                                      t.toolbar.get('spinner').hide());
                              }))));
                },
                createUploader: function () {
                    ((this.uploader = new media.view.UploaderInline({
                        controller: this.controller,
                        status: !1,
                        message:
                            this.controller.isModeActive('grid') ||
                            this.controller.isModeActive('eml-grid')
                                ? ''
                                : l10n.noItemsFound,
                        canClose:
                            this.controller.isModeActive('grid') ||
                            this.controller.isModeActive('eml-grid'),
                    })),
                        this.uploader.$el.addClass('hidden'),
                        this.views.add(this.uploader));
                },
                createAttachments: function () {
                    ((this.attachments = new media.view.Attachments({
                        controller: this.controller,
                        collection: this.collection,
                        selection: this.options.selection,
                        model: this.model,
                        sortable: this.options.sortable,
                        scrollElement: this.options.scrollElement,
                        idealColumnWidth: this.options.idealColumnWidth,
                        AttachmentView: this.options.AttachmentView,
                    })),
                        this.controller.on(
                            'attachment:keydown:arrow',
                            _.bind(this.attachments.arrowEvent, this.attachments),
                        ),
                        this.controller.on(
                            'attachment:details:shift-tab',
                            _.bind(this.attachments.restoreFocus, this.attachments),
                        ),
                        this.attachmentsWrapper
                            ? this.views.add('.attachments-wrapper', this.attachments)
                            : this.views.add(this.attachments),
                        (this.controller.isModeActive('grid') ||
                            this.controller.isModeActive('eml-grid')) &&
                            ((this.attachmentsNoResults = new media.View({
                                controller: this.controller,
                                tagName: 'p',
                            })),
                            this.attachmentsNoResults.$el.addClass('hidden no-media'),
                            this.attachmentsNoResults.$el.html(l10n.noMedia),
                            this.views.add(this.attachmentsNoResults)));
                },
            }),
            (allFilters = { input: 'onChange' }),
            _.extend(media.view.Search.prototype, {
                searchTerm: '',
                prevTerm: '',
                searchDelay: 1e3,
                timer: 0,
                events: allFilters,
                onChange: function (e) {
                    (clearTimeout(this.timer),
                        (this.searchTerm = e.target.value.trim()),
                        (this.searchDelay = 2e3 / this.searchTerm.length));
                    var i = this.controller.state().get('library');
                    this.searchTerm
                        ? this.searchTerm.length > 1 &&
                          ((!i.length &&
                              this.prevTerm.length &&
                              this.searchTerm.startsWith(this.prevTerm)) ||
                              (this.timer = setTimeout(
                                  _.bind(this.runSearch, this),
                                  this.searchDelay,
                              )))
                        : ((this.prevTerm = this.searchTerm), this.model.unset('search'));
                },
                runSearch: function () {
                    ((this.prevTerm = this.searchTerm), this.model.set('search', this.searchTerm));
                },
            }),
            $(document).ready(function () {
                $(document).on(
                    'mousedown',
                    '.media-frame .attachments-browser .attachments li',
                    function (e) {
                        (e.ctrlKey || e.shiftKey) && e.preventDefault();
                    },
                );
            }),
            $('body').addClass('eml-media-css'));
    })(jQuery, _));
