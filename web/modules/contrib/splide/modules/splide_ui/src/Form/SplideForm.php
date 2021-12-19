<?php

namespace Drupal\splide_ui\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\splide\Entity\Splide;
use Drupal\splide\SplideDefault;

/**
 * Extends base form for splide instance configuration form.
 */
class SplideForm extends SplideFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form      = parent::form($form, $form_state);
    $path      = drupal_get_path('module', 'splide');
    $splide    = $this->entity;
    $options   = $splide->getOptions() ?: [];
    $tooltip   = ['class' => ['is-tooltip']];
    $route     = ['name' => 'splide_ui'];
    $is_help   = $this->manager()->getModuleHandler()->moduleExists('help');
    $readme    = $is_help ? Url::fromRoute('help.page', $route)->toString() : Url::fromUri('base:' . $path . '/docs/README.md')->toString();
    $admin_css = $this->manager->configLoad('admin_css', 'blazy.settings');
    $defaults  = Splide::defaultSettings();

    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#default_value' => $splide->label(),
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#description'   => $this->t("Label for the Splide optionset."),
      '#attributes'    => $tooltip,
      '#prefix'        => '<div class="form__header form__half form__half--first has-tooltip clearfix">',
    ];

    // Keep the legacy CTools ID, i.e.: name as ID.
    $form['name'] = [
      '#type'          => 'machine_name',
      '#default_value' => $splide->id(),
      '#maxlength'     => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name'  => [
        'source' => ['label'],
        'exists' => '\Drupal\splide\Entity\Splide::load',
      ],
      '#attributes'    => $tooltip,
      '#disabled'      => !$splide->isNew(),
      '#suffix'        => '</div>',
    ];

    $form['skin'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Skin'),
      '#options'       => $this->admin->getSkinsByGroupOptions(),
      '#empty_option'  => $this->t('- None -'),
      '#default_value' => $splide->getSkin(),
      '#description'   => $this->t('Skins allow swappable layouts like next/prev links, split image and caption, etc. However a combination of skins and options may lead to unpredictable layouts, get yourself dirty. See main <a href="@url">README</a> for details on Skins. Only useful for custom work, and ignored/overridden by splide formatters or sub-modules. If you are using Splidebox, this is the only option to change its skin at the Splidebox optionset.', ['@url' => $readme]),
      '#attributes'    => $tooltip,
      '#prefix'        => '<div class="form__header form__half form__half--last has-tooltip clearfix">',
      '#wrapper_attributes' => ['class' => ['form-item--tooltip-wide']],
    ];

    $form['group'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Group'),
      '#options'       => [
        'main'     => $this->t('Main'),
        'nav'      => $this->t('Navigation'),
        'overlay'  => $this->t('Overlay'),
        'lightbox' => $this->t('Lightbox'),
      ],
      '#empty_option'  => $this->t('- None -'),
      '#default_value' => $splide->getGroup(),
      '#description'   => $this->t('Group this optionset to avoid confusion for optionset selections. Leave empty to make it available for all.'),
      '#attributes'    => $tooltip,
    ];

    $form['breakpoint'] = [
      '#title'         => $this->t('Breakpoint'),
      '#type'          => 'textfield',
      '#default_value' => $form_state->hasValue('breakpoint') ? $form_state->getValue('breakpoint') : $splide->getBreakpoint(),
      '#description'   => $this->t('The number of breakpoints added to Responsive display, max 9. This is not Breakpoint Width (480px, etc).'),
      '#ajax' => [
        'callback' => '::addBreakpoint',
        'wrapper'  => 'edit-breakpoint-ajax-wrapper',
        'event'    => 'change',
        'progress' => ['type' => 'fullscreen'],
        'effect'   => 'fade',
        'speed'    => 'fast',
      ],
      '#attributes' => $tooltip,
      '#maxlength'  => 1,
    ];

    $form['optimized'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Optimized'),
      '#default_value' => $splide->optimized(),
      '#description'   => $this->t('Check to optimize the stored options. Anything similar to defaults will not be stored, except those required by sub-modules and theme_splide(). Like you hand-code/ cherry-pick the needed options, and are smart enough to not repeat defaults, and free up memory. The rest are taken care of by JS. Uncheck only if theme_splide() can not satisfy the needs, and more hand-coded preprocess is needed which is less likely in most cases.'),
      '#access'        => $splide->id() != 'default',
      '#attributes'    => $tooltip,
      '#wrapper_attributes' => ['class' => ['form-item--tooltip-wide']],
    ];

    if ($splide->id() == 'default') {
      $form['breakpoint']['#suffix'] = '</div>';
    }
    else {
      $form['optimized']['#suffix'] = '</div>';
    }

    if ($admin_css) {
      $form['optimized']['#field_suffix'] = '&nbsp;';
      $form['optimized']['#title_display'] = 'before';
    }

    // Options.
    $form['options'] = [
      '#type'    => 'vertical_tabs',
      '#tree'    => TRUE,
      '#parents' => ['options'],
    ];

    // Main JS options.
    $form['settings'] = [
      '#type'       => 'details',
      '#tree'       => TRUE,
      '#title'      => $this->t('Settings'),
      '#attributes' => ['class' => ['details--settings', 'has-tooltip']],
      '#group'      => 'options',
      '#parents'    => ['options', 'settings'],
    ];

    foreach ($this->getFormElements() as $name => $element) {
      $element['default'] = isset($element['default']) ? $element['default'] : '';
      $default_value = (NULL !== $splide->getSetting($name)) ? $splide->getSetting($name) : $element['default'];
      $form['settings'][$name] = [
        '#title'         => isset($element['title']) ? $element['title'] : '',
        '#default_value' => $default_value,
      ];

      foreach (['type', 'options', 'empty_option', 'field_suffix', 'states'] as $option) {
        if (isset($element[$option])) {
          $form['settings'][$name]["#$option"] = $element[$option];
        }
      }
      if (isset($element['type'])) {
        if ($element['type'] != 'hidden') {
          $form['settings'][$name]['#attributes'] = $tooltip;
        }
        else {
          // Ensures hidden element doesn't screw up the states.
          unset($element['states']);
        }

        if ($element['type'] == 'textfield') {
          $form['settings'][$name]['#size'] = 20;
          $form['settings'][$name]['#maxlength'] = 255;
        }
      }

      if (isset($element['description'])) {
        $default = $this->getDefaultValue($defaults, $name);
        $form['settings'][$name]['#description'] = $element['description'] . $default;
      }

      if (is_int($element['default'])) {
        $form['settings'][$name]['#maxlength'] = 60;
        $form['settings'][$name]['#attributes']['class'][] = 'form-text--int';
      }

      if ($admin_css && !isset($element['field_suffix']) && is_bool($element['default'])) {
        $form['settings'][$name]['#field_suffix'] = '&nbsp;';
        $form['settings'][$name]['#title_display'] = 'before';
      }

      if (in_array($name, ['classes', 'i18n'])) {
        $form['settings'][$name]['#wrapper_attributes']['class'][] = 'form-item--tooltip-wide';
      }
    }

    // Responsive JS options.
    // https://github.com/Splidejs/splideissues/951
    $form['breakpoints'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Responsive display'),
      '#open'        => TRUE,
      '#tree'        => TRUE,
      '#group'       => 'options',
      '#parents'     => ['options', 'breakpoints'],
      '#description' => $this->t('Containing breakpoints and settings objects. Settings set at a given breakpoint/screen width is self-contained and does not inherit the main settings, but defaults. Be sure to set Breakpoint option above.'),
    ];

    $form['breakpoints']['responsive'] = [
      '#type'       => 'details',
      '#title'      => $this->t('Responsive'),
      '#open'       => TRUE,
      '#group'      => 'breakpoints',
      '#parents'    => ['options', 'breakpoints'],
      '#prefix'     => '<div id="edit-breakpoint-ajax-wrapper">',
      '#suffix'     => '</div>',
      '#attributes' => ['class' => ['has-tooltip', 'details--responsive--ajax']],
    ];

    // Add some information to the form state for easier form altering.
    $form_state->set('breakpoint_count', 0);
    $breakpoint_count = $form_state->hasValue('breakpoint') ? $form_state->getValue('breakpoint') : $splide->getBreakpoint();

    if (!$form_state->hasValue('breakpoint_count')) {
      $form_state->setValue('breakpoint_count', $breakpoint_count);
    }

    $user_input = $form_state->getUserInput();
    $breakpoint_input = isset($user_input['breakpoint']) ? (int) $user_input['breakpoint'] : $breakpoint_count;

    if ($breakpoint_input && ($breakpoint_input != $breakpoint_count)) {
      $form_state->setValue('breakpoint_count', $breakpoint_input);
    }

    if ($form_state->getValue('breakpoint_count') > 0) {
      $breakpoint_forms = $this->getResponsiveFormElements($form_state->getValue('breakpoint_count'));

      foreach ($breakpoint_forms as $i => $breakpoints) {
        // Individual breakpoint details depends on the breakpoint amount.
        $form['breakpoints']['responsive'][$i] = [
          '#type'       => $breakpoints['type'],
          '#title'      => $breakpoints['title'],
          '#open'       => FALSE,
          '#group'      => 'breakpoints',
          '#attributes' => [
            'class' => [
              'details--responsive',
              'details--breakpoint-' . $i,
              'has-tooltip',
            ],
          ],
        ];

        unset($breakpoints['title'], $breakpoints['type']);
        foreach ($breakpoints as $key => $responsive) {
          switch ($key) {
            case 'breakpoint':
            case 'unsplide':
              $form['breakpoints']['responsive'][$i][$key] = [
                '#type'          => $responsive['type'],
                '#title'         => $responsive['title'],
                '#default_value' => isset($options['breakpoints'][$i][$key]) ? $options['breakpoints'][$i][$key] : $responsive['default'],
                '#description'   => $responsive['description'],
                '#attributes'    => $tooltip,
              ];

              if ($responsive['type'] == 'textfield') {
                $form['breakpoints']['responsive'][$i][$key]['#size'] = 20;
                $form['breakpoints']['responsive'][$i][$key]['#maxlength'] = 255;
              }

              if (is_int($responsive['default'])) {
                $form['breakpoints']['responsive'][$i][$key]['#maxlength'] = 60;
              }

              if (isset($responsive['field_suffix'])) {
                $form['breakpoints']['responsive'][$i][$key]['#field_suffix'] = $responsive['field_suffix'];
              }

              if ($admin_css && !isset($responsive['field_suffix']) && $responsive['type'] == 'checkbox') {
                $form['breakpoints']['responsive'][$i][$key]['#field_suffix'] = '&nbsp;';
                $form['breakpoints']['responsive'][$i][$key]['#title_display'] = 'before';
              }
              break;

            case 'settings':
              $form['breakpoints']['responsive'][$i][$key] = [
                '#type'       => $responsive['type'],
                '#title'      => $responsive['title'],
                '#open'       => TRUE,
                '#group'      => $i,
                '#states'     => ['visible' => [':input[name*="options[breakpoints][' . $i . '][unsplide]"]' => ['checked' => FALSE]]],
                '#attributes' => [
                  'class' => [
                    'details--settings',
                    'details--breakpoint-' . $i,
                    'has-tooltip',
                  ],
                ],
              ];

              unset($responsive['title'], $responsive['type']);

              // @fixme, boolean default is ignored at index 0 only.
              foreach ($responsive as $k => $item) {
                $default = isset($defaults[$k]) ? $defaults[$k] : '';
                $item['default'] = isset($item['default']) ? $item['default'] : $default;

                $description = $this->getDefaultValue($defaults, $k);
                $description = isset($item['description']) ? $item['description'] . $description : '';

                $form['breakpoints']['responsive'][$i][$key][$k] = [
                  '#title'         => isset($item['title']) ? $item['title'] : '',
                  '#default_value' => isset($options['breakpoints'][$i][$key][$k]) ? $options['breakpoints'][$i][$key][$k] : $item['default'],
                  '#description'   => $description,
                  '#attributes'    => $tooltip,
                ];

                foreach (['type', 'options', 'empty_option', 'field_suffix'] as $option) {
                  if (isset($item[$option])) {
                    $form['breakpoints']['responsive'][$i][$key][$k]["#$option"] = $item[$option];
                  }
                }

                if ($admin_css && !isset($item['field_suffix']) && is_bool($item['default'])) {
                  $form['breakpoints']['responsive'][$i][$key][$k]['#field_suffix'] = '&nbsp;';
                  $form['breakpoints']['responsive'][$i][$key][$k]['#title_display'] = 'before';
                }
              }
              break;

            default:
              break;
          }
        }
      }
    }

    return $form;
  }

  /**
   * Defines available options for the main and responsive settings.
   *
   * @return array
   *   All available Splide options.
   *
   * @see https://github.com/Splidejs/splide
   */
  public function getFormElements() {
    if (!isset($this->formElements)) {
      $elements = [];

      $elements['type'] = [
        'type'        => 'select',
        'title'       => $this->t('Type'),
        'description' => $this->t("Determine a slider type, accepting: <br><b>slide</b>: Regular slider behavior. <br><b>loop</b>: Carousel slider. <br><b>fade</b>: Change slides with fade transition. perPage must be 1 and gap/padding must be 0.<br> Be warned! Only <b>slide</b> and <b>loop</b> make sense for thumbnail navigation."),
        'options'     => $this->manager->getTransitionTypes(),
      ];

      $media_queries = ['min', 'max'];
      $elements['mediaQuery'] = [
        'type'         => 'select',
        'title'        => $this->t('Media query'),
        'options'      => array_combine($media_queries, $media_queries),
        'description'  => $this->t('If min, the media query for breakpoints will be min-width, or otherwise max-width.'),
      ];

      $elements['rewind'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Rewind'),
        'description' => $this->t('Whether to rewind a slider before the first slide or after the last one.'),
      ];

      $elements['speed'] = [
        'type'         => 'number',
        'title'        => $this->t('Speed'),
        'description'  => $this->t('Transition speed in milliseconds.'),
        'field_suffix' => 'ms',
      ];

      $elements['rewindSpeed'] = [
        'type'         => 'number',
        'title'        => $this->t('Rewind speed'),
        'description'  => $this->t('Transition speed on rewind in milliseconds.'),
        'field_suffix' => 'ms',
      ];

      $elements['waitForTransition'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Wait for transition'),
        'description' => $this->t('Whether to prevent any actions while a slider is transitioning.'),
      ];

      $elements['width'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Width'),
        'description'  => $this->t('Defines the slider max width, accepting the CSS format such as 10em, 80vw. The example below sets the slider width to 80%.'),
      ];

      $elements['height'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Height'),
        'description'  => $this->t('Defines the slide height, accepting the CSS format except for %. Required for Vertical aka Dirction TTB.'),
      ];

      $elements['fixedWidth'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Fixed width'),
        'description'  => $this->t('Fixes width of slides, accepting the CSS format. The slider will ignore the perPage option if you provide this value.'),
      ];

      $elements['fixedHeight'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Fixed height'),
        'description'  => $this->t('Fixes height of slides, accepting the CSS format except for %. The slider will ignore height and heightRatio options if you provide this value.'),
      ];

      $elements['heightRatio'] = [
        'type'         => 'number',
        'title'        => $this->t('Height ratio'),
        'description'  => $this->t('Determines height of slides by the ratio to the slider width. For example, when the slider width is 1000 and the ratio is 0.3, the height will be 300.'),
      ];

      $elements['autoWidth'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Auto width'),
        'description' => $this->t('If true, the width of slides are determined by their width. The perPage and perMove options should be 1.'),
      ];

      $elements['autoHeight'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Auto height'),
        'description' => $this->t('If true, the height of slides are determined by their height. The perPage and perMove options should be 1.'),
      ];

      $elements['perPage'] = [
        'type'         => 'number',
        'title'        => $this->t('Per page'),
        'description'  => $this->t('Determine how many slides should be displayed per page.'),
      ];

      $elements['perMove'] = [
        'type'         => 'number',
        'title'        => $this->t('Per move'),
        'description'  => $this->t('Determine how many slides should be moved when a slider goes to next or previous page. To display all indicator dots, set focus to 0.'),
      ];

      $elements['clones'] = [
        'type'         => 'number',
        'title'        => $this->t('Clones'),
        'description'  => $this->t('Manually determine how many clones should be generated on one slide. The total number of clones will be twice of this for both sides. You don’t need to modify this value in most cases. Warning! Do not set this value if you unsure how this option affects the slider.'),
      ];

      $elements['start'] = [
        'type'         => 'number',
        'title'        => $this->t('Start index'),
        'description'  => $this->t('The initial slide, 0-based.'),
      ];

      $elements['focus'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Focus'),
        'description'  => $this->t('Determine which slide should be focused if there are multiple slides in a page. `center` is acceptable for centering an active slide. If you are not comfortable with empty spaces before the first slide and after the last one, enable <b>trimSpace</b> option (it is true as default).'),
      ];

      $elements['gap'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Gap'),
        'description'  => $this->t('Gap between slides. CSS format is allowed such as 1em.'),
      ];

      $elements['padding'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Padding'),
        'description'  => $this->t("Set padding-left/right in horizontal mode or padding-top/bottom in vertical one. E.g.: <code>{ left : 0, right: '2rem' }</code>, including braces _only for multiple values. Must be a valid JSON object. Or just <code>10</code> for single value without braces, meaning padding left/right(top/bottom) will be 10px."),
      ];

      $elements['easing'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Easing'),
        'description' => $this->t('Animation timing function for CSS transition. CSS3 animation easing. <a href="@ceaser">Learn</a> <a href="@bezier">more</a>. Ignored if <strong>CSS ease override</strong> is provided.', [
          '@ceaser' => '//matthewlein.com/ceaser/',
          '@bezier' => '//cubic-bezier.com',
        ]),
      ];

      $elements['easingOverride'] = [
        'title'        => $this->t('Easing override'),
        'type'         => 'select',
        'options'      => $this->getCssEasingOptions(),
        'empty_option' => $this->t('- None -'),
        'description'  => $this->t('If provided, this will override the Easing with the pre-defined CSS easings based on <a href="@ceaser">CSS Easing Animation Tool</a>. Leave it empty to use your own CSS ease.', ['@ceaser' => 'http://matthewlein.com/ceaser/']),
      ];

      $arrows = ['false', 'true', 'slider'];
      $elements['arrows'] = [
        'type'         => 'select',
        'title'        => $this->t('Arrows'),
        'options'      => array_combine($arrows, $arrows),
        'description'  => $this->t("Whether to append arrows. True, false, or slider. If true, arrows will be appended to the root element. If 'slider', they will be done to the slider element (HTML must have a slider div wrapping a track element)."),
      ];

      $elements['arrowPath'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Arrow path'),
        'description'  => $this->t("Change the arrow SVG path. The arrow SVG path like 'm7.61 0.807-2.12…'. SVG size must be 40×40."),
      ];

      $elements['down'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Use arrow down'),
        'description' => $this->t('Arrow down to scroll down into a certain page section. Be sure to provide its target selector.'),
      ];

      $elements['downTarget'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Arrow down target'),
        'description' => $this->t('Valid CSS selector to scroll to, e.g.: #main, or #content.'),
      ];

      $elements['downOffset'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Arrow down offset'),
        'description'  => $this->t('Offset when scrolled down from the top.'),
        'field_suffix' => 'px',
      ];

      $elements['pagination'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Pagination'),
        'description' => $this->t('Whether to append pagination (indicator dots). Change where the navigation dots are attached (Selector, htmlString). If you change this, be sure to provide its relevant markups. Try <strong>.splide__arrows</strong> to achieve this style: <br />&lt; o o o o o o o &gt;<br />Be sure to enable Arrows in such a case.'),
      ];

      $elements['autoplay'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Autoplay'),
        'description' => $this->t('Enables autoplay'),
      ];

      $elements['interval'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Autoplay interval'),
        'description' => $this->t('Autoplay interval in milliseconds.'),
      ];

      $elements['pauseOnHover'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Pause on hover'),
        'description' => $this->t('Whether to pause autoplay when a slider is hovered. The pauseOnHover option should be false (unchecked) if a slider has a pause button since autoplay will be stopped before clicking the button.'),
      ];

      $elements['pauseOnFocus'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Pause on focus'),
        'description' => $this->t('Whether to pause autoplay when elements inside a slider are focused. Checked (true) is recommended for accessibility.'),
      ];

      $elements['resetProgress'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Reset progress'),
        'description' => $this->t('Whether to reset progress of the autoplay timer when resumed.'),
      ];

      $elements['lazyLoad'] = [
        'type'         => 'select',
        'title'        => $this->t('Lazy load'),
        'options'      => $this->getLazyloadOptions(),
        'empty_option' => $this->t('- None -'),
        'description'  => $this->t("Set lazy loading technique. <br><b>None</b>: Disable lazy loading. <br><b>Nearby</b>: Only images around an active slide will be loaded (see Preload pages). <br><b>Sequential</b>: All images will be sequentially loaded. <br>To share images for Pinterest, leave empty, otherwise no way to read actual image src. It supports Blazy module to delay loading below-fold images until 100px before they are visible at viewport, and/or have a bonus lazyLoadAhead when the `move` event fired."),
      ];

      $elements['preloadPages'] = [
        'type'        => 'number',
        'title'       => $this->t('Preload pages'),
        'description' => $this->t('This option works only when a lazyLoad option is "Nearby". Determine how many pages (not slides) around an active slide should be loaded beforehand.'),
      ];

      $keyboards = ['false', 'true', 'focused'];
      $elements['keyboard'] = [
        'type'        => 'select',
        'title'       => $this->t('Keyboard'),
        'options'     => array_combine($keyboards, $keyboards),
        'description' => $this->t("Whether to control a slider via keyboard. <br><b>true or 'global'</b>: Listen to the keydown event of the document. <br><b>focused</b>: Listen to the keydown event of the slider root element. tabindex='0' will be added to it. <br><b>false</b>: Disable keyboard shortcuts. <br><br>If true or 'global' and there are multiple slides in a page, all slides will correspond with the same keyboard event. The ‘focused’ option can prevent this, but the slider needs to be focused by click or tab key."),
      ];

      $drags = ['false', 'true', 'free'];
      $elements['drag'] = [
        'type'        => 'select',
        'title'       => $this->t('Drag'),
        'options'     => array_combine($drags, $drags),
        'description' => $this->t('Whether to allow mouse drag and touch swipe.'),
      ];

      $elements['dragMinThreshold'] = [
        'type'        => 'number',
        'title'       => $this->t('Drag min threshold'),
        'description' => $this->t('The required distance to start moving the slider by the touch action. Other pointing devices will ignore this value.'),
      ];

      $elements['swipeDistanceThreshold'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Swipe distance threshold'),
        'description' => $this->t('Distance threshold for determining if the action is `flick` or `swipe`. When a drag distance is over this value, the action will be treated as `swipe`, not `flick`.'),
      ];

      $elements['flickVelocityThreshold'] = [
        'type'        => 'textfield',
        'title'       => $this->t('Flick velocity threshold'),
        'description' => $this->t('Velocity threshold for determining if a drag action is `flick` or `swipe`. Around 0.5 is recommended.'),
      ];

      $elements['flickPower'] = [
        'type'        => 'number',
        'title'       => $this->t('Flick power'),
        'description' => $this->t('Determine power of flick. The larger number this is, the farther a slider runs by flick.'),
      ];

      $elements['flickMaxPages'] = [
        'type'        => 'number',
        'title'       => $this->t('Flick max pages'),
        'description' => $this->t('Limit a number of pages to move by flick.'),
      ];

      $directions = ['ltr', 'rtl', 'ttb'];
      $elements['direction'] = [
        'type'        => 'select',
        'title'       => $this->t('Direction'),
        'options'     => array_combine($directions, $directions),
        'description' => $this->t("Slider direction. <br><b>ltr</b>: Left to right. <br><b>rtl</b>: Right to left. <br><b>ttb</b>: Top to bottom. <br>`height` or `heightRatio` option is required."),
      ];

      $elements['cover'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Cover'),
        'description' => $this->t('Set img src to background-image of its parent element. Images with various sizes can be displayed as same dimension without troublesome cropping work. height, fixedHeight or heightRatio is required. Overriden by formatter.'),
      ];

      $elements['slideFocus'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Slide focus'),
        'description' => $this->t('Whether to add tabindex="0" to visible slides or not.'),
      ];

      $elements['isNavigation'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Is navigation'),
        'description' => $this->t('Determine if a slider is navigation for another.'),
      ];

      $trims = ['false', 'true', 'move'];
      $elements['trimSpace'] = [
        'type'        => 'select',
        'title'       => $this->t('Trim space'),
        'options'     => array_combine($trims, $trims),
        'description' => $this->t("Whether to trim spaces before the first slide or after the last one. <br><b>false</b>: Allow spaces. <br><b>true</b>: Default. Remove spaces but sometimes the slider doesn’t move even when the active index is updated. <br><b>move</b>: Remove spaces and the slider always move when the active index is updated. This mode is not compatible with pagination(indicator dots)."),
      ];

      $elements['updateOnMove'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Update on move'),
        'description' => $this->t("The <code>is-active</code> class is added after transition is completed (the “moved” event) by default. If checked, it will be added before transition. <br>This doesn’t perfectly work when a slide and its clone are shown at the same time. For example, when number of slides is less than perPage or using fixedWidth with a few slides, you will see 2 (or more) active slides while transitioning."),
      ];

      // Module features.
      $elements['wheel'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Mouse wheel'),
        'description' => $this->t('Will use native mouse wheel event, see https://developer.mozilla.org/en-US/docs/Web/API/WheelEvent.'),
      ];

      $elements['randomize'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Randomize'),
        'description' => $this->t('Randomize the slide display, useful to manipulate cached blocks.'),
      ];

      $elements['classes'] = [
        'type'        => 'textarea',
        'title'       => $this->t('Classes'),
        'description' => $this->t("Collection of class names. To add your own classes to arrows or pagination buttons, provide them with original classes like this: <b><br><br>{ <br>arrows: 'splide__arrows your-class-arrows', <br>arrow: 'splide__arrow your-class-arrow', <br>prev: 'splide__arrow--prev your-class-prev', <br>next: 'splide__arrow--next your-class-next', <br>pagination: 'splide__pagination your-class-pagination', <br>page: 'splide__pagination__page your-class-page' <br>}</b><br><br>Including braces. Must be a valid JSON object like the sample. Be sure the original class (the first one) is always included to avoid broken displays."),
      ];

      $elements['i18n'] = [
        'type'        => 'textarea',
        'title'       => $this->t('i18n'),
        'description' => $this->t("Here is a list of default texts. `%s` will be replaced by a slide or page number: <b><br><br>{ <br>prev: 'Previous slide', <br>next: 'Next slide', <br>first: 'Go to first slide', <br>last:	'Go to last slide', <br>slideX:	'Go to slide %s', <br>pageX: 'Go to page %s', <br>play: 'Start autoplay',
        <br>pause: 'Pause autoplay' <br>}</b><br><br>Including braces. Must be a valid JSON object like the sample."),
      ];

      // Defines the default values if available.
      $defaults = Splide::defaultSettings();
      foreach ($elements as $name => $element) {
        $default = $element['type'] == 'checkbox' ? FALSE : '';
        $elements[$name]['default'] = isset($defaults[$name]) ? $defaults[$name] : $default;
      }

      foreach (Splide::getDependentOptions() as $parent => $items) {
        foreach ($items as $name) {
          if (isset($elements[$name])) {
            // Arrows is not a checkbox, can be boolean or string.
            if ($parent == 'arrows') {
              $states = ['visible' => ['select[name*="options[settings][' . $parent . ']"]' => ['!value' => 'false']]];
            }
            else {
              $states = ['visible' => [':input[name*="options[settings][' . $parent . ']"]' => ['checked' => TRUE]]];
            }

            if (!isset($elements[$name]['states'])) {
              $elements[$name]['states'] = $states;
            }
            else {
              $elements[$name]['states'] = array_merge($elements[$name]['states'], $states);
            }
          }
        }
      }

      $this->formElements = $elements;
    }

    return $this->formElements;
  }

  /**
   * Defines available options for the responsive Splide.
   *
   * @param int $count
   *   The number of breakpoints.
   *
   * @return array
   *   An array of Splide responsive options.
   */
  public function getResponsiveFormElements($count = 0) {
    $elements = [];
    $range = range(0, ($count - 1));
    $breakpoints = array_combine($range, $range);

    foreach ($breakpoints as $key => $breakpoint) {
      $elements[$key] = [
        'type'  => 'details',
        'title' => $this->t('Breakpoint #@key', ['@key' => ($key + 1)]),
      ];

      $elements[$key]['breakpoint'] = [
        'type'         => 'textfield',
        'title'        => $this->t('Breakpoint'),
        'description'  => $this->t('Breakpoint width in pixel.'),
        'default'      => '',
        'field_suffix' => 'px',
      ];

      $elements[$key]['unsplide'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Destroy'),
        'description' => $this->t("Disable Splide at a given breakpoint. Note, you can't window shrink this, once you destroy, you are destroyed."),
        'default'     => FALSE,
      ];

      $elements[$key]['settings'] = [
        'type'  => 'details',
        'title' => $this->t('Settings'),
      ];

      // Duplicate relevant main settings.
      $valid = SplideDefault::validBreakpointOptions();
      $valid = array_combine($valid, $valid);
      foreach ($this->getFormElements() as $name => $responsive) {
        if (!isset($valid[$name])) {
          continue;
        }
        $elements[$key]['settings'][$name] = $responsive;
      }
    }
    return $elements;
  }

  /**
   * Returns modifiable lazyload options.
   */
  public function getLazyloadOptions() {
    $options = [
      'blazy' => $this->t('Blazy'),
      'nearby' => $this->t('Nearby'),
      'sequential' => $this->t('Sequential'),
    ];

    $this->manager->getModuleHandler()->alter('splide_lazyload_options', $options);
    return $options;
  }

  /**
   * Defines options required by theme_splide(), used with optimized option.
   */
  public function getOptionsRequiredByTemplate() {
    $options = [
      'lazyLoad' => '',
      'perPage' => 0,
    ];

    $this->manager->getModuleHandler()->alter('splide_options_required_by_template', $options);
    return $options;
  }

  /**
   * Returns the typecast values.
   *
   * @param array $settings
   *   An array of Optionset settings.
   */
  public function typecastOptionset(array &$settings = []) {
    if (empty($settings)) {
      return;
    }

    $defaults = Splide::defaultSettings();

    foreach ($defaults as $name => $value) {
      if (isset($settings[$name])) {
        // Seems double is ignored, and causes a missing schema, unlike float.
        $type = gettype($defaults[$name]);
        $type = $type == 'double' ? 'float' : $type;

        settype($settings[$name], $type);
      }
    }
  }

  /**
   * Handles switching the breakpoint based on the input value.
   */
  public function addBreakpoint($form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('breakpoint')) {
      $form_state->setValue('breakpoint_count', $form_state->getValue('breakpoint'));
      if ($form_state->getValue('breakpoint') >= 6) {
        $message = $this->t('You are trying to load too many Breakpoints. Try reducing it to reasonable numbers say, between 1 to 5.');
        $this->messenger()->addMessage($message, 'warning');
      }
    }

    return $form['breakpoints']['responsive'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Update CSS Bezier version.
    $override = $form_state->getValue(['options', 'settings', 'easingOverride']);
    if ($override) {
      $override = $this->getBezier($override);
      // Update cssEaseBezier value based on easingOverride.
      $form_state->setValue(['options', 'settings', 'easing'], $override);
    }

    // @todo Prevents hybrid casts from screwing up.
    $num = $form_state->getValue('breakpoint');
    $breakpoints = $form_state->getValue(['options', 'breakpoints']);
    if (!empty($breakpoints)) {
      foreach ($breakpoints as $key => $breakpoint) {
        if (empty($breakpoint['breakpoint'])) {
          $form_state->unsetValue(['options', 'breakpoints', $key]);
          $num -= 1;
          $form_state->setValue('breakpoint', $num);
        }
      }
    }

    if ($form_state->getValue(['options', 'options__active_tab'])) {
      $form_state->unsetValue(['options', 'options__active_tab']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Optimized if so configured.
    $splide  = $this->entity;
    $default = $splide->id() == 'default';
    if (!$default && !$form_state->isValueEmpty('optimized')) {
      $defaults = $splide::defaultSettings();
      $required = $this->getOptionsRequiredByTemplate();
      $main     = array_diff_assoc($defaults, $required);
      $settings = $form_state->getValue(['options', 'settings']);

      // Cast the values.
      $this->typecastOptionset($settings);

      // Remove wasted dependent options if disabled, empty or not.
      $splide->removeWastedDependentOptions($settings);

      $main_settings = array_diff_assoc($settings, $main);
      $splide->setSettings($main_settings);

      $responsive_options = ['options', 'breakpoints'];
      if ($breakpoints = $form_state->getValue($responsive_options)) {
        foreach ($breakpoints as $delta => &$responsive) {
          if (!empty($responsive['unsplide'])) {
            $splide->setResponsiveSettings([], $delta);
          }
          else {
            $this->typecastOptionset($responsive['settings']);
            $splide->removeWastedDependentOptions($responsive['settings']);

            $responsive_settings = array_diff_assoc($responsive['settings'], $defaults);
            $splide->setResponsiveSettings($responsive_settings, $delta);
          }
        }
      }
    }
  }

  /**
   * Returns default value.
   */
  private function getDefaultValue(array $defaults, $name): string {
    $default = '';
    if (isset($defaults[$name])) {
      $value = $defaults[$name];
      if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
      }
      elseif (is_string($value)) {
        $value = empty($value) ? $this->t('None') : $value;
      }
      $default = ' <em>' . $this->t('Default: @value', ['@value' => $value]) . '</em>';
    }
    return $default;
  }

}
