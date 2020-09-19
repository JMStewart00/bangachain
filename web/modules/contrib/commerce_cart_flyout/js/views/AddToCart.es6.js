(($, Backbone, _, Drupal, drupalSettings) => {
  Drupal.addToCart.AddToCartView = Backbone.View.extend(/** @lends Drupal.cartFlyout.CartBlockView# */{
    initialize() {
      const defaultVariation = this.model.getVariation(this.model.getDefaultVariation());
      this._populateSelectedAttributes(defaultVariation);
      this.render();
    },
    events: {
      'click .form-submit': 'addToCart',
      'change .attribute-widgets input[type="radio"]': 'onAttributeChange',
      'change .attribute-widgets select': 'onAttributeChange',
      'change .variations-select select': 'onVariationTitleChange',
    },
    _populateSelectedAttributes(variation) {
      _.each(this.model.getAttributes(), (attribute, i) => {
        let attributeFieldName = 'attribute_' + attribute.id;
        this.selectedAttributes[attributeFieldName] = variation[attributeFieldName]
      });
    },
    _injectVariationFields(variation) {
      const injectedFields = this.model.getInjectedFieldsForVariation(variation.uuid);
      Object.values(injectedFields).map(injectedField => $(`.${injectedField.class}`).replaceWith(injectedField.output));
    },
    onVariationTitleChange(event) {
      Drupal.detachBehaviors();
      const selectedVariation = this.model.getVariation(event.target.value);
      this.model.setSelectedVariation(selectedVariation.uuid);
      this._injectVariationFields(selectedVariation);
      Drupal.attachBehaviors();
    },
    onAttributeChange(event) {
      Drupal.detachBehaviors();
      this.selectedAttributes[event.target.name] = parseInt(event.target.value, 10);
      const selectedVariation = this.model.getResolvedVariation(this.selectedAttributes);
      this.model.setSelectedVariation(selectedVariation.uuid);
      this._populateSelectedAttributes(selectedVariation);
      this._injectVariationFields(selectedVariation);
      this.render();
      Drupal.attachBehaviors(this.$el.get(0), _.extend({}, drupalSettings, {
        triggeringAttribute: event.target.name,
        selectedAttributes: this.selectedAttributes,
        selectedVariation,
      }));
    },
    addToCart() {
      const selectedVariation = this.model.getSelectedVariation();
      $.ajax({
        url: Drupal.url('cart/add?_format=json'),
        method: 'POST',
        data: JSON.stringify([
          {
            purchased_entity_type: 'commerce_product_variation',
            purchased_entity_id: selectedVariation.variation_id,
            quantity: 1
          }
        ]),
        contentType: 'application/json;',
        dataType: 'json',
      })
        .done(() => {
          Drupal.cartFlyout.fetchCarts();
          Drupal.cartFlyout.flyoutOffcanvasToggle();
        });
    },
    render() {
      if (this.model.getVariationCount() === 1) {
        this.$el.html(Drupal.theme('addToCartButton'));
      }
      else if (this.model.getAttributes().length === 0 || this.model.getType() !== 'commerce_product_variation_attributes') {
        let html = [
          '<div class="variations-select form-group">'
        ];

        const variations = this.model.getVariations();
        html.push(Drupal.theme('addToCartVariationSelect', {
          variations: Object.keys(variations).map(uuid => variations[uuid])
        }));

        html.push('</div>');
        html.push(Drupal.theme('addToCartButton'));
        this.$el.html(html.join(''));
      }
      else {
        const view = this;
        const html = [
          '<div class="attribute-widgets form-group">'
        ];
        const currentVariation = view.model.getSelectedVariation();
        this.model.getAttributes().forEach(entry => {
          const defaultArgs = {
            currentVariation,
            targetVariations: [],
            label: entry.label,
            attributeId: entry.id,
            attributeValues: entry.values,
            activeValue: view.selectedAttributes['attribute_' + entry.id]
          };
          if (typeof defaultArgs.activeValue === 'object') {
            defaultArgs.activeValue = defaultArgs.activeValue.attribute_value_id;
          }
          _.each(entry.values, attributeValue => {
            const selectedAttributes = _.extend({}, view.selectedAttributes)
            selectedAttributes['attribute_' + entry.id] = attributeValue.attribute_value_id;
            defaultArgs.targetVariations[attributeValue.attribute_value_id] = view.model.getResolvedVariation(selectedAttributes);
          });

          if (entry.element_type === 'select') {
            html.push(Drupal.theme('addToCartAttributesSelect', defaultArgs))
          }
          else if (entry.element_type === 'radios') {
            html.push(Drupal.theme('addToCartAttributesRadios', defaultArgs))
          }
          else if (entry.element_type === 'commerce_product_rendered_attribute') {
            html.push(Drupal.theme('addToCartAttributesRendered', Object.assign({}, defaultArgs, {
              attributeValues: view.model.getRenderedAttribute('attribute_' + entry.id)
            })))
          }
        });
        html.push('</div>');
        html.push(Drupal.theme('addToCartButton'));
        this.$el.html(html.join(''));
      }
    }
  });
  Drupal.addToCart.AddToCartView.prototype.selectedAttributes = {};
})(jQuery, Backbone, _, Drupal, drupalSettings);
