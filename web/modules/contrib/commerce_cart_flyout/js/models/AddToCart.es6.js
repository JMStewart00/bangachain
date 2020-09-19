((Backbone, Drupal) => {
  Drupal.addToCart.AddToCartModel = Backbone.Model.extend(/** @lends Drupal.addToCart.AddToCartModel# */{
    defaults: {
      defaultVariation: '',
      selectedVariation: '',
      attributeOptions: {},
      injectedFields: {},
      variations: {},
      variationCount: 0,
      type: 'commerce_product_variation_attributes',
    },
    initialize() {
      this.set('variationCount', Object.keys(this.get('variations')).length);
      this.set('selectedVariation', this.getVariation(this.get('defaultVariation')))
    },
    getDefaultVariation() {
      return this.get('defaultVariation');
    },
    getAttributes() {
      return this.get('attributeOptions')[this.getSelectedVariation().uuid].attributes;
    },
    getVariations() {
      return this.get('variations');
    },
    getVariation(uuid) {
      return this.attributes['variations'][uuid]
    },
    getResolvedVariation(selectedAttributes) {
      function getSelectedAttributeValueId(fieldName) {
        if (typeof selectedAttributes[fieldName] === 'object') {
          return selectedAttributes[fieldName].attribute_value_id.toString();
        }
        return selectedAttributes[fieldName].toString();
      }
      function getVariationAttributeValueId(fieldName, variation) {
        if (!variation.hasOwnProperty(fieldName)) {
          return '';
        }
        if (typeof variation[fieldName] === 'object') {
          return variation[fieldName].attribute_value_id.toString();
        }
        return variation[fieldName].toString()
      }

      const attributes = [...this.getAttributes()];
      const variations = Object.keys(this.getVariations()).map(key => this.getVariation(key));
      let selectedVariation = null;

      while (attributes.length > 0) {
        for (let variation of variations) {
          let match = true;
          for (let attribute of attributes) {
            let fieldName = 'attribute_' + attribute.id;
            if (getVariationAttributeValueId(fieldName, variation) !== getSelectedAttributeValueId(fieldName)) {
              match = false;
            }
          }
          if (match) {
            selectedVariation = variation;
            break;
          }
        }
        if (selectedVariation !== null) {
          break;
        }
        // Pop off the last attribute as we had no matches, go through the values again.
        attributes.pop();
      }
      return selectedVariation;
    },
    getSelectedVariation() {
      return this.attributes['selectedVariation'];
    },
    setSelectedVariation(uuid) {
      this.set('selectedVariation', this.getVariation(uuid));
    },
    getVariationCount() {
      return this.get('variationCount');
    },
    getRenderedAttribute(fieldName) {
      return this.get('attributeOptions')[this.getSelectedVariation().uuid].renderedAttributes[fieldName];
    },
    getInjectedFieldsForVariation(uuid) {
      return this.attributes['injectedFields'][uuid];
    },
    getType() {
      return this.attributes['type'];
    }
  });
})(Backbone, Drupal);
