<?php
defined('ABSPATH') || exit;
?>
<div v-cloak>
  <nav class="nav-tab-wrapper">
    <a :href="'admin.php?page=secure-passkeys-settings#/'" class="nav-tab">
      <?php esc_html_e('General Settings', 'secure-passkeys'); ?>
    </a>
    <a
      :href="'admin.php?page=secure-passkeys-settings#/display-settings'"
      class="nav-tab nav-tab-active"
    >
      <?php esc_html_e('Display Settings', 'secure-passkeys'); ?>
    </a>
    <a
      :href="'admin.php?page=secure-passkeys-settings#/advanced-settings'"
      class="nav-tab"
    >
      <?php esc_html_e('Advanced Settings', 'secure-passkeys'); ?>
    </a>
  </nav>

  <h1>
    <i v-if="isLoading" class="spinner is-active spin"></i>
  </h1>

  <transition name="fade">
    <div class="error" v-if="errorMessage != ''">
      <p>{{ errorMessage }}</p>
    </div>
    <div class="updated" v-if="successMessage != ''">
      <p>{{ successMessage }}</p>
    </div>
  </transition>

  <div :class="{'loading-blur': isLoading}">
    <h2><?php esc_html_e('Frontend Options', 'secure-passkeys'); ?></h2>
  </div>

  <table class="form-table" :class="{'loading-blur': isLoading}" width="100%">
    <tbody>
      <tr>
        <th style="width: 200px">
          <label for="display_passkey_theme" class="inline-label">
            <?php esc_html_e('Theme', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <select
            name="display_passkey_theme"
            id="display_passkey_theme"
            v-model="settings.display_passkey_theme"
            :disabled="submitting || isLoading"
          >
            <option
              v-for="(label, value) in defaults.themes"
              :key="value"
              :value="value"
            >
              {{ label }}
            </option>
          </select>
          <div>
          <p class="help">
            <?php esc_html_e('Choose a frontend theme. All themes, except "default", must be activated to function properly.', 'secure-passkeys'); ?>
            <br />
            <?php esc_html_e('You can add custom themes using a plugin filters.', 'secure-passkeys'); ?>
          </p>
          </div>
        </td>
      </tr>
    </tbody>
  </table>

  <hr :class="{'loading-blur': isLoading}" />

  <div :class="{'loading-blur': isLoading}">
    <p>
      <?php esc_html_e('Configure the Passkey login options to be displayed on the frontend for default WordPress and third-party plugins.', 'secure-passkeys'); ?>
    </p>
  </div>

  <table class="form-table" :class="{'loading-blur': isLoading}" width="100%">
    <tbody>
      <tr>
        <th style="width: 200px">
          <label for="display_passkey_login_wp_enabled" class="inline-label">
            <?php esc_html_e('WordPress Login Form', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <label for="display_passkey_login_wp_enabled">
            <input
              name="display_passkey_login_wp_enabled"
              type="checkbox"
              id="display_passkey_login_wp_enabled"
              v-model="settings.display_passkey_login_wp_enabled"
              true-value="1"
              false-value="0"
              :disabled="submitting || isLoading"
            />
            <?php esc_html_e('Enable Passkey Login for default WordPress login.', 'secure-passkeys'); ?>
          </label>
        </td>
      </tr>
    </tbody>
  </table>
  <div :class="{'loading-blur': isLoading}">
    <p>
      <?php esc_html_e('These options will work only if the respective plugins are installed and activated:', 'secure-passkeys'); ?>
    </p>
  </div>
  <table class="form-table" :class="{'loading-blur': isLoading}" width="100%">
    <tbody>
      <tr>
        <th style="width: 200px">
          <label
            for="display_passkey_login_woocommerce_enabled"
            class="inline-label"
          >
            <?php esc_html_e('Login for WooCommerce', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <label for="display_passkey_login_woocommerce_enabled">
            <input
              name="display_passkey_login_woocommerce_enabled"
              type="checkbox"
              id="display_passkey_login_woocommerce_enabled"
              v-model="settings.display_passkey_login_woocommerce_enabled"
              true-value="1"
              false-value="0"
              :disabled="submitting || isLoading"
            />
            <?php esc_html_e('Enable Passkey Login for WooCommerce login page.', 'secure-passkeys'); ?>
          </label>
          <div>
            <p class="help">
              <?php
              // translators: %s represents the name of the required plugin (e.g., "WooCommerce").
              printf(esc_html__('The %s plugin must be installed and activated to work correctly.', 'secure-passkeys'), 'WooCommerce');
              ?>
            </p>
          </div>
        </td>
      </tr>
      <tr>
        <th style="width: 200px">
          <label
            for="display_passkey_login_memberpress_enabled"
            class="inline-label"
          >
            <?php esc_html_e('Login for MemberPress', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <label for="display_passkey_login_memberpress_enabled">
            <input
              name="display_passkey_login_memberpress_enabled"
              type="checkbox"
              id="display_passkey_login_memberpress_enabled"
              v-model="settings.display_passkey_login_memberpress_enabled"
              true-value="1"
              false-value="0"
              :disabled="submitting || isLoading"
            />
            <?php esc_html_e('Enable Passkey Login for MemberPress login form.', 'secure-passkeys'); ?>
          </label>
          <div>
            <p class="help">
              <?php
              // translators: %s represents the name of the required plugin (e.g., "MemberPress").
              printf(esc_html__('The %s plugin must be installed and activated to work correctly.', 'secure-passkeys'), 'MemberPress');
              ?>
            </p>
          </div>
        </td>
      </tr>
      <tr>
        <th style="width: 200px">
          <label for="display_passkey_login_edd_enabled" class="inline-label">
            <?php esc_html_e('Login for Easy Digital Downloads', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <label for="display_passkey_login_edd_enabled">
            <input
              name="display_passkey_login_edd_enabled"
              type="checkbox"
              id="display_passkey_login_edd_enabled"
              v-model="settings.display_passkey_login_edd_enabled"
              true-value="1"
              false-value="0"
              :disabled="submitting || isLoading"
            />
            <?php esc_html_e('Enable Passkey Login for Easy Digital Downloads login form.', 'secure-passkeys'); ?>
          </label>
          <div>
            <p class="help">
              <?php
              // translators: %s represents the name of the required plugin (e.g., "Easy Digital Downloads").
              printf(esc_html__('The %s plugin must be installed and activated to work correctly.', 'secure-passkeys'), 'Easy Digital Downloads');
              ?>
            </p>
          </div>
        </td>
      </tr>
      <tr>
        <th style="width: 200px">
          <label for="display_passkey_login_ultimate_member_enabled" class="inline-label">
            <?php esc_html_e('Login for Ultimate Member', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <label for="display_passkey_login_ultimate_member_enabled">
            <input
              name="display_passkey_login_ultimate_member_enabled"
              type="checkbox"
              id="display_passkey_login_ultimate_member_enabled"
              v-model="settings.display_passkey_login_ultimate_member_enabled"
              true-value="1"
              false-value="0"
              :disabled="submitting || isLoading"
            />
            <?php esc_html_e('Enable Passkey Login for Ultimate Member login form.', 'secure-passkeys'); ?>
          </label>
          <div>
            <p class="help">
              <?php
              // translators: %s represents the name of the required plugin (e.g., "Ultimate Member").
              printf(esc_html__('The %s plugin must be installed and activated to work correctly.', 'secure-passkeys'), 'Ultimate Member');
              ?>
            </p>
          </div>
        </td>
      </tr>
    </tbody>
  </table>

  <hr :class="{'loading-blur': isLoading}" />

  <div :class="{'loading-blur': isLoading}">
    <p class="settings-description">
      <?php esc_html_e("Use the following shortcodes to embed the passkey login and registration forms on your frontend pages. These shortcodes are typically only necessary if you need to manually embed the forms on custom pages. By default, the login form handles various options automatically, and you may not need to manage it unless you're working with a custom page.", 'secure-passkeys'); ?>  </p>
    </p>
  </div>
  <table class="form-table" :class="{'loading-blur': isLoading}" width="100%">
  <tbody>
    <tr>
      <th style="width: 200px">
        <label for="login_shortcode" class="inline-label">
          <?php esc_html_e('Login Short Code', 'secure-passkeys'); ?>
        </label>
      </th>
      <td>
        <input
              name="login_shortcode"
              type="text"
              :disabled="submitting || isLoading"
              value="[secure_passkeys_login_form]"
              style="width: 250px;"
              readonly="readonly"
            />
        <div>
          <p class="help">
            <?php esc_html_e('This shortcode to embed the "Login via Passkey" login button,this allows users to log in using their passkey credentials.', 'secure-passkeys'); ?>
            <br />
            <?php esc_html_e('If the user is already logged in, the form will not be displayed.', 'secure-passkeys'); ?>
          </p>
        </div>
      </td>
    </tr>

    <tr>
      <th style="width: 200px">
        <label for="register_shortcode" class="inline-label">
          <?php esc_html_e('Register Short Code', 'secure-passkeys'); ?>
        </label>
      </th>
      <td>
        <input
              name="register_shortcode"
              type="text"
              :disabled="submitting || isLoading"
              value="[secure_passkeys_register_form]"
              style="width: 250px;"
              readonly="readonly"
            />
        <div>
          <p class="help">
          <?php esc_html_e('This shortcode to embed the passkey registration form. This allows logged-in users to create and manage their passkey credentials.', 'secure-passkeys'); ?>
            <br />
            <?php esc_html_e('If the user is not logged in, the form will not be displayed.', 'secure-passkeys'); ?>
          </p>
        </div>
      </td>
    </tr>
  </tbody>
  </table>

  <hr :class="{'loading-blur': isLoading}" />

  <div :class="{'loading-blur': isLoading}">
    <h2><?php esc_html_e('Adminarea Options', 'secure-passkeys'); ?></h2>
    <p>
      <?php esc_html_e('Configure the options available in the admin area.', 'secure-passkeys'); ?>
    </p>
  </div>

  <table class="form-table" :class="{'loading-blur': isLoading}" width="100%">
    <tbody>
      <tr>
        <th style="width: 200px">
          <label for="display_passkey_users_list_enabled" class="inline-label">
            <?php esc_html_e('Display Passkeys in Users List', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <label for="display_passkey_users_list_enabled">
            <input
              name="display_passkey_users_list_enabled"
              type="checkbox"
              id="display_passkey_users_list_enabled"
              v-model="settings.display_passkey_users_list_enabled"
              true-value="1"
              false-value="0"
              :disabled="submitting || isLoading"
            />
            <?php esc_html_e('Enable this option to show the passkeys as a column in the users list in the adminarea.', 'secure-passkeys'); ?>
          </label>
        </td>
      </tr>
      <tr>
        <th style="width: 200px">
          <label for="display_passkey_edit_user_enabled" class="inline-label">
            <?php esc_html_e('Display Passkeys in Edit User/Profile', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <label for="display_passkey_edit_user_enabled">
            <input
              name="display_passkey_edit_user_enabled"
              type="checkbox"
              id="display_passkey_edit_user_enabled"
              v-model="settings.display_passkey_edit_user_enabled"
              true-value="1"
              false-value="0"
              :disabled="submitting || isLoading"
            />
            <?php esc_html_e('Enable this option to show the passkeys on the Edit User and Profile page in the adminarea.', 'secure-passkeys'); ?>
          </label>
        </td>
      </tr>
    </tbody>
  </table>

  <table class="form-table" :class="{'loading-blur': isLoading}" width="100%">
    <tbody>
      <tr>
        <th></th>
        <td>
          <button
            @click="updateSettings()"
            type="button"
            class="button button-primary"
            id="submit"
            :disabled="submitting || isLoading"
          >
            <i
              v-if="submitting"
              class="spinner is-active spin spinner-button"
            ></i>
            <span v-if="!submitting"
              ><?php esc_html_e('Save Changes', 'secure-passkeys'); ?></span
            >
            <span v-else
              ><?php esc_html_e('Saving...', 'secure-passkeys'); ?></span
            >
          </button>
        </td>
      </tr>
    </tbody>
  </table>
</div>
