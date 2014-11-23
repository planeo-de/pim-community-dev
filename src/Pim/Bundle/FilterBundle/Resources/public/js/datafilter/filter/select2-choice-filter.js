define(
    ['jquery', 'underscore', 'oro/datafilter/text-filter', 'routing', 'jquery.select2'],
    function($, _, TextFilter, Routing) {
        'use strict';

        return TextFilter.extend({
            operatorChoices: [],
            choiceUrl: null,
            choiceUrlParams: {},
            emptyChoice: false,
            resultCache: {},
            resultsPerPage: 20,
            popupCriteriaTemplate: _.template(
                '<div class="choicefilter">' +
                    '<div class="input-prepend">' +
                        '<div class="btn-group">' +
                            '<% if (emptyChoice) { %>' +
                                '<button class="btn dropdown-toggle" data-toggle="dropdown">' +
                                    '<%= selectedOperatorLabel %>' +
                                    '<span class="caret"></span>' +
                                '</button>' +
                                '<ul class="dropdown-menu">' +
                                    '<% _.each(operatorChoices, function (label, operator) { %>' +
                                        '<li<% if (selectedOperator == operator) { %> class="active"<% } %>>' +
                                            '<a class="operator_choice" href="#" data-value="<%= operator %>"><%= label %></a>' +
                                        '</li>' +
                                    '<% }); %>' +
                                '</ul>' +
                            '<% } %>' +
                            '<input type="text" name="value" value=""/>' +
                        '</div>' +
                    '</div>' +
                    '<div class="btn-group">' +
                        '<button type="button" class="btn btn-primary filter-update"><%- _.__("Update") %></button>' +
                    '</div>' +
                '</div>'
            ),

            events: {
                'click .operator_choice': '_onSelectOperator'
            },

            initialize: function(options) {
                _.extend(this.events, TextFilter.prototype.events);

                options = options || {};
                if (_.has(options, 'choiceUrl')) {
                    this.choiceUrl = options.choiceUrl;
                }
                if (_.has(options, 'choiceUrlParams')) {
                    this.choiceUrlParams = options.choiceUrlParams;
                }
                if (_.has(options, 'emptyChoice')) {
                    this.emptyChoice = options.emptyChoice;
                }

                if (_.isUndefined(this.emptyValue)) {
                    this.emptyValue = {
                        type: 'in',
                        value: ''
                    };
                }

                TextFilter.prototype.initialize.apply(this, arguments);
            },

            _onSelectOperator: function(e) {
                $(e.currentTarget).parent().parent().find('li').removeClass('active');
                $(e.currentTarget).parent().addClass('active');
                var parentDiv = $(e.currentTarget).parent().parent().parent();

                if ($(e.currentTarget).attr('data-value') === 'empty') {
                    this._disableInput();
                } else {
                    this._enableInput();
                }
                parentDiv.find('button').html($(e.currentTarget).html() + '<span class="caret"></span>');
                e.preventDefault();
            },

            _enableInput: function() {
                this.$(this.criteriaValueSelectors.value).select2(this._getSelect2Config());
                this.$(this.criteriaValueSelectors.value).show();
            },

            _disableInput: function() {
                this.$(this.criteriaValueSelectors.value).val('').select2('destroy');
                this.$(this.criteriaValueSelectors.value).hide();
            },

            _getSelect2Config: function() {
                var config = {
                    multiple: true,
                    allowClear: false,
                    width: '290px',
                    minimumInputLength: 0
                };

                config.ajax = {
                    url: Routing.generate(this.choiceUrl, this.choiceUrlParams),
                    cache: true,
                    data: _.bind(function(term, page) {
                        return {
                            search: term,
                            options: {
                                limit: this.resultsPerPage,
                                page: page
                            }
                        };
                    }, this),
                    results: _.bind(function(data) {
                        this._cacheResults(data.results);
                        data.more = this.resultsPerPage === data.results.length;

                        return data;
                    }, this)
                };

                return config;
            },

            _writeDOMValue: function(value) {
                this.$('li .operator_choice[data-value="' + value.type + '"]').trigger('click');
                var operator = this.$('li.active .operator_choice').data('value');
                if ('empty' === operator) {
                    this._setInputValue(this.criteriaValueSelectors.value, []);
                } else {
                    this._setInputValue(this.criteriaValueSelectors.value, value.value);
                }

                return this;
            },

            _readDOMValue: function() {
                var operator = this.emptyChoice ? this.$('li.active .operator_choice').data('value') : 'in';

                return {
                    value: operator === 'empty' ? {} : this._getInputValue(this.criteriaValueSelectors.value),
                    type: operator
                };
            },

            _renderCriteria: function(el) {
                this.operatorChoices = {
                    'in':    _.__('pim.grid.choice_filter.label_in_list'),
                    'empty': _.__('pim.grid.choice_filter.label_empty')
                };

                $(el).append(
                    this.popupCriteriaTemplate({
                        emptyChoice:           this.emptyChoice,
                        selectedOperatorLabel: this.operatorChoices[this.emptyValue.type],
                        operatorChoices:       this.operatorChoices,
                        selectedOperator:      this.emptyValue.type
                    })
                );

                this.$(this.criteriaValueSelectors.value).select2(this._getSelect2Config());
            },

            _onClickCriteriaSelector: function(e) {
                e.stopPropagation();
                $('body').trigger('click');
                if (!this.popupCriteriaShowed) {
                    this._showCriteria();
                    this.$(this.criteriaValueSelectors.value).select2('open');
                } else {
                    this._hideCriteria();
                }
            },

            _onClickCloseCriteria: function() {
                TextFilter.prototype._onClickCloseCriteria.apply(this, arguments);

                this.$(this.criteriaValueSelectors.value).select2('close');
            },

            _onClickOutsideCriteria: function(e) {
                var elem = this.$(this.criteriaSelector);

                if (e.target != $('body').get(0) && e.target !== elem.get(0) && !elem.has(e.target).length) {
                    this._hideCriteria();
                    this.setValue(this._formatRawValue(this._readDOMValue()));
                    e.stopPropagation();
                }
            },

            _cacheResults: function (results) {
                _.each(results, function (result) {
                    this.resultCache[result.id] = result.text;
                }, this);
            },

            _getCachedResults: function(ids) {
                var results = [],
                    missingResults = [];

                _.each(ids, function(id) {
                    if (_.isUndefined(this.resultCache[id])) {
                        missingResults.push(id);
                    } else {
                        results.push({ id: id, text: this.resultCache[id] });
                    }
                }, this);

                if (missingResults.length) {
                    var params = { options: { ids: missingResults } };

                    $.ajax({
                        url: Routing.generate(this.choiceUrl, this.choiceUrlParams) + '&' + $.param(params),
                        success: _.bind(function(data) {
                            this._cacheResults(data.results);
                            results = _.union(results, data.results);
                        }, this),
                        async: false
                    });
                }

                return results;
            },

            _getInputValue: function(input) {
                return this.$(input).select2('val');
            },

            _setInputValue: function(input, value) {
                this.$(input).select2('data', this._getCachedResults(value));

                return this;
            },

            _updateDOMValue: function() {
                return this._writeDOMValue(this.getValue());
            },

            _formatDisplayValue: function(value) {
                if (_.isEmpty(value.value)) {
                    return value;
                }

                return {
                    value: _.pluck(this._getCachedResults(value.value), 'text').join(', ')
                };
            },

            _getCriteriaHint: function() {
                var operator = this.$('li.active .operator_choice').data('value');
                if ('empty' === operator) {
                    return this.operatorChoices[operator];
                }

                var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();
                return !_.isEmpty(value.value) ? '"' + value.value + '"': this.placeholder;
            }
        });
    }
);
