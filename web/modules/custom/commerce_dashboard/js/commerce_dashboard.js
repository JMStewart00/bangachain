var markup = `<div id="commerce-dashboard">
  <v-app>
    <v-content>
      <v-container>
        <v-row>

          <v-col
            cols="4"
          >
            <v-card
              class="mx-auto"
            >
              <v-list-item three-line>
                <v-list-item-avatar><v-icon large>mdi-currency-usd</v-icon></v-list-item-avatar>
                <v-list-item-content>
                  <div class="overline mb-4">${Drupal.t('Sales today')}</div>
                  <v-list-item-title class="headline mb-1">{{ sales.today.amount }} {{ currencyCode }}</v-list-item-title>
                  <v-list-item-subtitle>
                    <v-icon
                      color="green"
                      v-if="sales.today.changeIndicator === 'increased'"
                    >mdi-chevron-up</v-icon>
                    <v-icon
                      color="red"
                      v-if="sales.today.changeIndicator === 'decreased'"
                    >mdi-chevron-down</v-icon>
                    Sales {{ sales.today.changeIndicator }}
                  </v-list-item-subtitle>
                </v-list-item-content>
              </v-list-item>
            </v-card>
          </v-col>

          <v-col
            cols="4"
          >
            <v-card
              class="mx-auto"
            >
              <v-list-item three-line>
                <v-list-item-avatar><v-icon large>mdi-currency-usd</v-icon></v-list-item-avatar>
                <v-list-item-content>
                  <div class="overline mb-4">${Drupal.t('Sales yesterday')}</div>
                  <v-list-item-title class="headline mb-1">{{ sales.yesterday.amount }} {{ currencyCode }}</v-list-item-title>
                  <v-list-item-subtitle>
                    <v-icon
                      color="green"
                      v-if="sales.yesterday.changeIndicator === 'increased'"
                    >mdi-chevron-up</v-icon>
                    <v-icon
                      color="red"
                      v-if="sales.yesterday.changeIndicator === 'decreased'"
                    >mdi-chevron-down</v-icon>
                    Sales {{ sales.yesterday.changeIndicator }}
                  </v-list-item-subtitle>
                </v-list-item-content>
              </v-list-item>

            </v-card>          </v-col>

          <v-col
            cols="4"
          >
            <v-card
              class="mx-auto"
            >
              <v-list-item three-line>
                <v-list-item-avatar><v-icon large>mdi-currency-usd</v-icon></v-list-item-avatar>
                <v-list-item-content>
                  <div class="overline mb-4">${Drupal.t('Sales current week')}</div>
                  <v-list-item-title class="headline mb-1">{{ sales.week.amount }} {{ currencyCode }}</v-list-item-title>
                  <v-list-item-subtitle>
                    <v-icon
                      color="green"
                      v-if="sales.week.changeIndicator === 'increased'"
                    >mdi-chevron-up</v-icon>
                    <v-icon
                      color="red"
                      v-if="sales.week.changeIndicator === 'decreased'"
                    >mdi-chevron-down</v-icon>
                    Sales {{ sales.week.changeIndicator }}
                  </v-list-item-subtitle>
                </v-list-item-content>
              </v-list-item>

            </v-card>
          </v-col>
        </v-row>

        <v-row>
          <v-col
            cols="8"
          >
            <v-card
              class="mx-auto"
              color="lighten-4"
            >
              <v-card-title>
                <v-icon
                  class="mr-12"
                  size="64"
                >
                  mdi-chart-line-variant
                </v-icon>
                <v-row align="start">
                  <div class="title">
                  ${Drupal.t('Sales over the last 14 days')}
                  </div>
                </v-row>

                <v-spacer></v-spacer>

                <v-btn icon class="align-self-start" size="28">
                  <v-icon>mdi-arrow-right-thick</v-icon>
                </v-btn>
              </v-card-title>

              <v-sheet color="transparent">
                <v-sparkline
                  :value="salesWeekAmounts"
                  :gradient="gradient"
                  smooth="10"
                  padding="8"
                  line-width="2"
                  stroke-linecap="round"
                  gradient-direction="top"
                  type="trend"
                  label-size="4"
                  :labels="salesWeekLabels"
                  auto-draw
                ></v-sparkline>
              </v-sheet>
            </v-card>

          </v-col>

          <v-col
            cols="4"
          >
            <v-card>
              <v-card-title>
                <v-icon
                  class="mr-12"
                  size="64"
                >
                  mdi-chart-line-variant
                </v-icon>
                <v-row align="start">
                  <div class="title">
                  ${Drupal.t('Top selling products')}
                  </div>
                </v-row>

                <v-spacer></v-spacer>
              </v-card-title>

              <v-list two-line>
                  <v-divider></v-divider>

                  <v-list-item
                    v-for="product in topProducts"
                    :key="product.productId"
                    :href="'/product/' + product.productId"
                  >
                    <v-list-item-content>
                      <v-list-item-title>{{ product.title }}</v-list-item-title>
                      <v-list-item-subtitle>Total purchases: {{ product.purchases }}</v-list-item-subtitle>
                    </v-list-item-content>

                    <div class="title">{{ product.total }} {{ currencyCode }}</div>
                  </v-list-item>
                <v-divider></v-divider>
              </v-list>

            </v-card>
          </v-col>
        </v-row>

      </v-container>
    </v-content>
  </v-app>
</div>
`

var app = new Vue({
  el: '#commerce-dashboard',
  vuetify: new Vuetify(),
  filters: {
    dateFormat (val) {
      const date = moment.unix(val)
      return date.format('MM.DD.YYYY hh:mm')
    }
  },
  data: {
    // Real data
    sales: {
      today: {
        amount: 0,
        changeIndicator: 'increase',
        changePercentage: 0
      },
      yesterday: {
        amount: 0,
        changeIndicator: 'increase',
        changePercentage: 0
      },
      week: {
        amount: 0,
        changeIndicator: 'increase',
        changePercentage: 0
      },
    },
    currencyCode: '',
    loading: false,
    salesWeekLabels: [],
    salesWeekAmounts: [],
    topProducts: [],
    gradient: ['green', 'yellow', 'red'],
    search: '',
    filter: {},
    sortDesc: false,
    page: 1,
    sortBy: 'name',
  },
  template: markup,
  computed: {
    numberOfPages () {
      return Math.ceil(this.items.length / this.cartsPerPage)
    },
    filteredKeys () {
      return this.keys.filter(key => key !== `Name`)
    },
  },
  methods: {
    nextPage () {
      if (this.page + 1 <= this.numberOfPages) this.page += 1
    },
    formerPage () {
      if (this.page - 1 >= 1) this.page -= 1
    },
    loadData () {
      if(this.loading === false) {
        this.loading = true
        axios
          .get('/commerce_dashboard/data')
          .then(response => {
            this.sales = response.data.sales
            console.log(response.data);
            const lineChartData = Object.entries(response.data.sales.lineChart)
            this.salesWeekAmounts = []
            this.salesWeekLabels = []
            for (const [day, amount] of Object.entries(response.data.sales.lineChart) ) {
              const date = moment(day)
              this.salesWeekLabels.push(date.format('MM.DD'))
              this.salesWeekAmounts.push(amount)
            }
            this.topProducts = []
            for (const [productId, product] of Object.entries(response.data.topProducts) ) {
              this.topProducts.push({
                title: product.title,
                purchases: product.purchases,
                productId: product.purchased_entity,
                total: product.total
              })
            }
            this.loading = false;
            this.currencyCode = response.data.currencyCode
          })
      }
    }
  },
  mounted: function () {
    this.loadData();
    setInterval(function () {
      this.loadData();
    }.bind(this), 1000 * 60);
  }
})
