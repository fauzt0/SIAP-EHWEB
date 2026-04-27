<nav id="sidebar" class="sidebar">
  <div class="sidebar-content js-simplebar">
    <a class="sidebar-brand" href="index.html" style="padding-top: 1rem; padding-bottom: 0rem;">
      <img src="<?php echo base_url() ?>bootstrap/img/brands/logo-eh-horizontal.svg" alt="" class="sidebar-brand-icons"
        width="180px">
    </a>

    <ul class="sidebar-nav">
      <li class="sidebar-header">
        Navigation
      </li>
      <li class="sidebar-item <?= url_is('nat/dashboard*') || url_is('nat') ? 'active' : '' ?>">
        <a data-bs-target="#dashboards" data-bs-toggle="collapse"
          class="sidebar-link <?= url_is('nat/dashboard*') || url_is('nat') ? '' : 'collapsed' ?>">
          <i class="align-middle" data-lucide="sliders"></i> <span class="align-middle">Dashboards</span>
          <span class="badge badge-sidebar-primary">5</span>
        </a>
        <ul id="dashboards"
          class="sidebar-dropdown list-unstyled collapse <?= url_is('nat/dashboard*') || url_is('nat') ? 'show' : '' ?>"
          data-bs-parent="#sidebar">
          <li class="sidebar-item active"><a class="sidebar-link" href="dashboard-default.html">Default</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="dashboard-analytics.html">Analytics</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="dashboard-saas.html">SaaS</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="dashboard-social.html">Social</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="dashboard-crypto.html">Crypto</a></li>
        </ul>
      </li>
      <?php if (auth()->user()->can('admin.manage-users')): ?>
        <li class="sidebar-item <?= url_is('nat/user*') ? 'active' : '' ?>">
          <a data-bs-target="#users-nav" data-bs-toggle="collapse"
            class="sidebar-link <?= url_is('nat/user*') ? '' : 'collapsed' ?>">
            <i class="align-middle" data-lucide="users"></i> <span class="align-middle">Usuarios</span>
          </a>
          <ul id="users-nav" class="sidebar-dropdown list-unstyled collapse <?= url_is('nat/user*') ? 'show' : '' ?> "
            data-bs-parent="#sidebar">
            <li class="sidebar-item <?= url_is('nat/user') ? 'active' : '' ?>"><a class="sidebar-link"
                href="<?= base_url('nat/user') ?>">Administrar</a></li>
            <li class="sidebar-item <?= url_is('nat/user/roles') ? 'active' : '' ?>"><a class="sidebar-link"
                href="<?= base_url('nat/user/roles') ?>">Roles</a></li>
          </ul>
        </li>
      <?php endif; ?>
      
      <?php if (auth()->user()->can('hr.access')): ?>
        <li class="sidebar-item <?= url_is('nat/hr*') ? 'active' : '' ?>">
          <a data-bs-target="#hr-nav" data-bs-toggle="collapse"
            class="sidebar-link <?= url_is('nat/hr*') ? '' : 'collapsed' ?>">
            <i class="align-middle" data-lucide="briefcase"></i> <span class="align-middle">Recursos Humanos</span>
          </a>
          <ul id="hr-nav" class="sidebar-dropdown list-unstyled collapse <?= url_is('nat/hr*') ? 'show' : '' ?> "
            data-bs-parent="#sidebar">
            <li class="sidebar-item <?= url_is('nat/hr') || url_is('nat/hr/workers') ? 'active' : '' ?>">
              <a class="sidebar-link" href="<?= base_url('nat/hr/workers') ?>">Trabajadores</a>
            </li>
            <li class="sidebar-item <?= url_is('nat/hr/worker/new') ? 'active' : '' ?>">
              <a class="sidebar-link" href="<?= base_url('nat/hr/worker/new') ?>">Nuevo Ingreso</a>
            </li>
            <li class="sidebar-item <?= url_is('nat/hr/catalogs*') ? 'active' : '' ?>">
              <a class="sidebar-link" href="<?= base_url('nat/hr/catalogs') ?>">Catálogos</a>
            </li>
            <li class="sidebar-item <?= url_is('nat/hr/contracts/templates*') ? 'active' : '' ?>">
              <a class="sidebar-link" href="<?= base_url('nat/hr/contracts/templates') ?>">Plantillas (Contratos)</a>
            </li>
          </ul>
        </li>
      <?php endif; ?>

      <li class="sidebar-header">
        Apps
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#ecommerce" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="shopping-bag"></i> <span class="align-middle">E-Commerce</span>
        </a>
        <ul id="ecommerce" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="ecommerce-products.html">
              Products <span class="badge badge-sidebar-primary">New</span>
            </a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ecommerce-products-details.html">
              Product Details <span class="badge badge-sidebar-primary">New</span>
            </a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ecommerce-orders.html">
              Orders <span class="badge badge-sidebar-primary">New</span>
            </a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ecommerce-customers.html">Customers</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ecommerce-invoice.html">Invoice</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ecommerce-pricing.html">Pricing</a></li>
        </ul>
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#projects" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="layout"></i> <span class="align-middle">Projects</span>
        </a>
        <ul id="projects" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="projects-overview.html">Overview</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="projects-details.html">Details</a></li>
        </ul>
      </li>
      <li class="sidebar-item">
        <a class="sidebar-link" href="chat.html">
          <i class="align-middle" data-lucide="list"></i> <span class="align-middle">Chat</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a class="sidebar-link" href="file-manager.html">
          <i class="align-middle" data-lucide="files"></i> <span class="align-middle">File Manager</span>
          <span class="badge badge-sidebar-primary">New</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a class="sidebar-link" href="calendar.html">
          <i class="align-middle" data-lucide="calendar"></i> <span class="align-middle">Calendar</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#email" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="mail"></i> <span class="align-middle">Email</span>
          <span class="badge badge-sidebar-primary">New</span>
        </a>
        <ul id="email" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="email-inbox.html">Inbox</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="email-details.html">Details</a></li>
        </ul>
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#tasks" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="trello"></i> <span class="align-middle">Tasks</span>
        </a>
        <ul id="tasks" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="tasks-list.html">
              List
              <span class="badge badge-sidebar-primary">New</span>
            </a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="tasks-kanban.html">Kanban</a></li>
        </ul>
      </li>
      <li class="sidebar-header">
        Pages
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#pages" data-bs-toggle="collapse" class="sidebar-link">
          <i class="align-middle" data-lucide="layout"></i> <span class="align-middle">Pages</span>
        </a>
        <ul id="pages" class="sidebar-dropdown list-unstyled collapse show" data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="pages-profile.html">Profile</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="pages-settings.html">Settings</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="pages-blank.html">Blank Page</a></li>
        </ul>
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#auth" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="users"></i> <span class="align-middle">Auth</span>
          <span class="badge badge-sidebar-secondary">Special</span>
        </a>
        <ul id="auth" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="auth-sign-in.html">Sign In</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="auth-sign-in-cover.html">Sign In Cover</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="auth-sign-up.html">Sign Up</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="auth-sign-up-cover.html">Sign Up Cover</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="auth-reset-password.html">Reset Password</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="auth-reset-password-cover.html">Reset Password
              Cover</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="auth-lock-screen.html">Lock Screen</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="auth-lock-screen-cover.html">Lock Screen Cover</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="auth-2fa.html">2FA</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="auth-2fa-cover.html">2FA Cover</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="auth-404.html">404 Page</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="auth-500.html">500 Page</a></li>
        </ul>
      </li>
      <li class="sidebar-item">
        <a class="sidebar-link" href="landing.html" target="_blank">
          <i class="align-middle" data-lucide="layout-template"></i> <span class="align-middle">Landing</span>
          <span class="badge badge-sidebar-primary">New</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#docs" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="book-open"></i> <span class="align-middle">Documentation</span>
        </a>
        <ul id="docs" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="docs-introduction.html">Introduction</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="docs-installation.html">Getting Started</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="docs-customization.html">Customization</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="docs-plugins.html">Plugins</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="docs-changelog.html">Changelog</a></li>
        </ul>
      </li>

      <li class="sidebar-header">
        Tools & Components
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#ui" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="grid"></i> <span class="align-middle">UI Elements</span>
        </a>
        <ul id="ui" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="ui-alerts.html">Alerts</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ui-buttons.html">Buttons</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ui-cards.html">Cards</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ui-carousel.html">Carousel</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ui-embed-video.html">Embed Video</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ui-general.html">General <span
                class="badge badge-sidebar-primary">10+</span></a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ui-grid.html">Grid</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ui-modals.html">Modals</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ui-offcanvas.html">Offcanvas</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ui-placeholders.html">Placeholders</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ui-tabs.html">Tabs</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="ui-typography.html">Typography</a></li>
        </ul>
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#icons" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="heart"></i> <span class="align-middle">Icons</span>
          <span class="badge badge-sidebar-primary">1500+</span>
        </a>
        <ul id="icons" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="icons-lucide.html">Lucide</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="icons-font-awesome.html">Font Awesome</a></li>
        </ul>
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#forms" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="check-square"></i> <span class="align-middle">Forms</span>
        </a>
        <ul id="forms" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="forms-layouts.html">Layouts</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="forms-basic-inputs.html">Basic Inputs</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="forms-input-groups.html">Input Groups</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="forms-floating-labels.html">Floating Labels</a></li>
        </ul>
      </li>
      <li class="sidebar-item">
        <a class="sidebar-link" href="tables.html">
          <i class="align-middle" data-lucide="list"></i> <span class="align-middle">Tables</span>
        </a>
      </li>

      <li class="sidebar-header">
        Plugins & Addons
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#forms-plugins" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="check-square"></i> <span class="align-middle">Form Plugins</span>
        </a>
        <ul id="forms-plugins" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="forms-advanced-inputs.html">Advanced Inputs</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="forms-editors.html">Editors</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="forms-validation.html">Validation</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="forms-wizard.html">Wizard</a></li>
        </ul>
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#datatables" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="list"></i> <span class="align-middle">DataTables</span>
        </a>
        <ul id="datatables" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="datatables-responsive.html">Responsive Table</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="datatables-buttons.html">Table with Buttons</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="datatables-column-search.html">Column Search</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="datatables-fixed-header.html">Fixed Header</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="datatables-multi.html">Multi Selection</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="datatables-ajax.html">Ajax Sourced Data</a></li>
        </ul>
      </li>

      <li class="sidebar-item">
        <a data-bs-target="#charts" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="pie-chart"></i> <span class="align-middle">Charts</span>
          <span class="badge badge-sidebar-primary">New</span>
        </a>
        <ul id="charts" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="charts-chartjs.html">Chart.js</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="charts-apexcharts.html">ApexCharts <span
                class="badge badge-sidebar-primary">New</span></a></li>
        </ul>
      </li>
      <li class="sidebar-item">
        <a class="sidebar-link" href="notifications.html">
          <i class="align-middle" data-lucide="bell"></i> <span class="align-middle">Notifications</span>
        </a>
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#maps" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="map-pin"></i> <span class="align-middle">Maps</span>
        </a>
        <ul id="maps" class="sidebar-dropdown list-unstyled collapse " data-bs-parent="#sidebar">
          <li class="sidebar-item"><a class="sidebar-link" href="maps-google.html">Google Maps</a></li>
          <li class="sidebar-item"><a class="sidebar-link" href="maps-vector.html">Vector Maps</a></li>
        </ul>
      </li>
      <li class="sidebar-item">
        <a data-bs-target="#multi" data-bs-toggle="collapse" class="sidebar-link collapsed">
          <i class="align-middle" data-lucide="share-2"></i> <span class="align-middle">Multi Level</span>
        </a>
        <ul id="multi" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
          <li class="sidebar-item">
            <a data-bs-target="#multi-2" data-bs-toggle="collapse" class="sidebar-link collapsed">
              Two Levels
            </a>
            <ul id="multi-2" class="sidebar-dropdown list-unstyled collapse">
              <li class="sidebar-item">
                <a class="sidebar-link" data-bs-target="#">Item 1</a>
                <a class="sidebar-link" data-bs-target="#">Item 2</a>
              </li>
            </ul>
          </li>
          <li class="sidebar-item">
            <a data-bs-target="#multi-3" data-bs-toggle="collapse" class="sidebar-link collapsed">
              Three Levels
            </a>
            <ul id="multi-3" class="sidebar-dropdown list-unstyled collapse">
              <li class="sidebar-item">
                <a data-bs-target="#multi-3-1" data-bs-toggle="collapse" class="sidebar-link collapsed">
                  Item 1
                </a>
                <ul id="multi-3-1" class="sidebar-dropdown list-unstyled collapse">
                  <li class="sidebar-item">
                    <a class="sidebar-link" data-bs-target="#">Item 1</a>
                    <a class="sidebar-link" data-bs-target="#">Item 2</a>
                  </li>
                </ul>
              </li>
              <li class="sidebar-item">
                <a class="sidebar-link" data-bs-target="#">Item 2</a>
              </li>
            </ul>
          </li>
        </ul>
      </li>
    </ul>

    <div class="sidebar-cta">
      <div class="sidebar-cta-content">
        <strong class="d-inline-block mb-2">Monthly Sales Report</strong>
        <div class="mb-3 text-sm">
          Your monthly sales report is ready for download!
        </div>

        <div class="d-grid">
          <a href="https://themes.getbootstrap.com/product/appstack-responsive-admin-template/" class="btn btn-primary"
            target="_blank">Download</a>
        </div>
      </div>
    </div>
  </div>
</nav>