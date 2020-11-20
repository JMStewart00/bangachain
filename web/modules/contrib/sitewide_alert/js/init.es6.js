// import React from 'react';
// import ReactDom from 'react-dom';
(function(Drupal, drupalSettings) {
  // window.sitewideAlert = sitewideAlert || {};
  // const { SitewideAlert } = ./components/SitewideAlert.js;

  class SitewideAlert extends React.Component {
    constructor(props) {
      super(props);
      this.state = {
        dismissed: this.alertWasDismissed(props.dismissalIgnoreBefore),
        showOnThisPage: this.shouldShowOnThisPage(props.showOnPages, props.negateShowOnPages)
      };
      this.dismissAlert = this.dismissAlert.bind(this);
      this.alertWasDismissed = this.alertWasDismissed.bind(this);
      this.shouldShowOnThisPage = this.shouldShowOnThisPage.bind(this);
    }

    componentDidUpdate(prevProps) {
      if (
        this.props.dismissalIgnoreBefore !== prevProps.dismissalIgnoreBefore ||
        this.props.showOnPages !== prevProps.showOnPages ||
        this.props.negateShowOnPages !== prevProps.negateShowOnPages
      ) {
        this.setState({
          dismissed: this.alertWasDismissed(this.props.dismissalIgnoreBefore),
          showOnThisPage: this.shouldShowOnThisPage(this.props.showOnPages, this.props.negateShowOnPages)
        });
      }
    }

    shouldShowOnThisPage(pages = [], negate = true) {
      if (pages.length === 0) {
        return true;
      }

      let pagePathMatches = false;
      const currentPath = window.location.pathname;

      for (let i = 0; i < pages.length; i++) {
        const baseUrl = drupalSettings.path.baseUrl.slice(0, -1);
        const page = baseUrl + pages[i];
        // Check if we have to deal with a wild card.
        if (page.charAt(page.length - 1) === '*') {
          if (currentPath.startsWith(page.substring(0, page.length - 1))) {
            pagePathMatches = true;
            break;
          }
        } else if (page === currentPath) {
          pagePathMatches = true;
          break;
        }
      }

      return negate ? !pagePathMatches : pagePathMatches;
    }

    alertWasDismissed(ignoreDismissalBefore) {
      if (!('alert-dismissed-' + this.props.uuid in window.localStorage)) {
        return false;
      }

      const dismissedAtTimestamp = Number(
        window.localStorage.getItem('alert-dismissed-' + this.props.uuid),
      );

      // If the visitor has already dismissed the alert but we are supposed to ignore dismissals before a set date.
      if (dismissedAtTimestamp < ignoreDismissalBefore) {
        return false;
      }

      return true;
    }

    dismissAlert() {
      window.localStorage.setItem('alert-dismissed-' + this.props.uuid, String(Math.round((new Date()).getTime() / 1000)));
      this.setState({
        dismissed: true,
        showOnThisPage: this.state.showOnThisPage,
      });
    }

    render() {
      // Prevent the alert from showing if it has been dismissed.
      if (this.props.dismissible && this.state.dismissed) {
        return null;
      }

      if (!this.state.showOnThisPage) {
        return null;
      }

      // Set alert classes.
      let alertClasses = 'sitewide-alert alert';
      if (this.props.styleClass !== '') {
        alertClasses += ' ' + this.props.styleClass;
      }

      return (
        <div className={alertClasses} role="alert">
          {/* The inner HTML was already processed for XSS by Drupal's render method. */}
          <span dangerouslySetInnerHTML={{__html: this.props.message}}/>
          {this.props.dismissible && <button className="close" onClick={this.dismissAlert} aria-label="Close"><span aria-hidden="true">&times;</span></button>}
        </div>
      );
    }
  }

  class SitewideAlerts extends React.Component {
    constructor(props) {
      super(props);
      this.state = {
        error: null,
        isLoaded: false,
        sitewideAlerts: [],
      };
    }

    componentDidMount() {
      this.getAlerts();
      if(drupalSettings.sitewideAlert.automaticRefresh === true) {
        this.interval = setInterval(() => {
          this.getAlerts();
        }, (drupalSettings.sitewideAlert.refreshInterval < 1000) ? 1000 : drupalSettings.sitewideAlert.refreshInterval);
      }
    }

    componentWillUnmount() {
      clearInterval(this.interval);
    }

    getAlerts() {
      fetch(window.location.origin + drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix + 'sitewide_alert/load')
        .then(res => res.json())
        .then(
          (result) => {
            this.setState({
              isLoaded: true,
              sitewideAlerts: result.sitewideAlerts,
            });
          },
          // Note: it's important to handle errors here
          // instead of a catch() block so that we don't swallow
          // exceptions from actual bugs in components.
          (error) => {
            this.setState({
              isLoaded: true,
              error,
            });
          },
        );
    }

    render() {
      const { error, isLoaded, sitewideAlerts } = this.state;
      if (error) {
        console.log('Unable to to load alerts.');
        return <div/>;
      } else if (!isLoaded) {
        return <div/>;
      } else {
        return (
          <div>
            {sitewideAlerts.map(sitewideAlert => (
              <SitewideAlert
                key={sitewideAlert.uuid}
                uuid={sitewideAlert.uuid}
                message={sitewideAlert.message}
                styleClass={sitewideAlert.styleClass}
                dismissible={sitewideAlert.dismissible}
                dismissalIgnoreBefore={sitewideAlert.dismissalIgnoreBefore}
                showOnPages={sitewideAlert.showOnPages}
                negateShowOnPages={sitewideAlert.negateShowOnPages}
              />
            ))}
          </div>
        );
      }
    }
  }

  Drupal.behaviors.sitewide_alert_init = {
    attach: (context, settings) => {
      ReactDOM.render(
        <SitewideAlerts />,
        document.getElementById('sitewide-alert'),
      );
    },
  };
})(Drupal, drupalSettings);
