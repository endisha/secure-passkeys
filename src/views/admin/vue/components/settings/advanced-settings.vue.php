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
      class="nav-tab"
    >
      <?php esc_html_e('Display Settings', 'secure-passkeys'); ?>
    </a>
    <a
      :href="'admin.php?page=secure-passkeys-settings#/advanced-settings'"
      class="nav-tab nav-tab-active"
    >
      <?php esc_html_e('Advanced Settings', 'secure-passkeys'); ?>
    </a>
  </nav>

  <h1>
    <i v-if="isLoading" class="spinner is-active spin"></i>
  </h1>

  <transition name="fade">
    <div class="error" v-if="errorMessage">
      <p>{{ errorMessage }}</p>
    </div>
    <div class="updated" v-if="successMessage">
      <p>{{ successMessage }}</p>
    </div>
  </transition>

  <div :class="{'loading-blur': isLoading}">
    <h2><?php esc_html_e('Advanced Settings', 'secure-passkeys'); ?></h2>
    <p>
      <?php esc_html_e('Manage advanced options for plugin maintenance.', 'secure-passkeys'); ?>
    </p>
  </div>

  <table class="form-table" :class="{'loading-blur': isLoading}" width="100%">
    <tbody>
      <!-- Challenge Cleanup Settings -->
      <tr>
        <th style="width: 200px">
          <label for="challenge_cleanup_days" class="inline-label">
            <?php esc_html_e('Delete passkey challenge records older than', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <label for="challenge_cleanup_days">
            <select
              name="challenge_cleanup_days"
              id="challenge_cleanup_days"
              v-model="settings.challenge_cleanup_days"
              :disabled="submitting || isLoading"
            >
              <option
                v-for="days in defaults.challenge_cleanup_days_periods"
                :key="days"
                :value="days"
              >
                <template v-if="days === 0">
                  <?php esc_html_e('Never', 'secure-passkeys'); ?>
                </template>
                <template v-else>
                  <?php esc_html_e('Older than', 'secure-passkeys'); ?>
                  {{ days }}
                  <?php esc_html_e('days', 'secure-passkeys'); ?>
                </template>
              </option>
            </select>
            <p class="help">
              <?php esc_html_e('Automatically removes outdated passkey challenge records to optimize database performance. If set to "Never", records will not be deleted.', 'secure-passkeys'); ?>
            </p>
          </label>
        </td>
      </tr>

      <tr>
        <th style="width: 200px">
          <label for="log_cleanup_days" class="inline-label">
            <?php esc_html_e('Delete log records older than', 'secure-passkeys'); ?>
          </label>
        </th>
        <td>
          <label for="log_cleanup_days">
            <select
              name="log_cleanup_days"
              id="log_cleanup_days"
              v-model="settings.log_cleanup_days"
              :disabled="submitting || isLoading"
            >
              <option
                v-for="days in defaults.log_cleanup_days_periods"
                :key="days"
                :value="days"
              >
                <template v-if="days === 0">
                  <?php esc_html_e('Never', 'secure-passkeys'); ?>
                </template>
                <template v-else>
                  <?php esc_html_e('Older than', 'secure-passkeys'); ?>
                  {{ days }}
                  <?php esc_html_e('days', 'secure-passkeys'); ?>
                </template>
              </option>
            </select>
            <p class="help">
              <?php esc_html_e('(Not recommended) Automatically deletes outdated log records. If set to "Never", logs will not be removed.', 'secure-passkeys'); ?>
              <br />
              <span style="text-decoration: underline"
                ><?php esc_html_e('This results in the loss of activity log history.', 'secure-passkeys'); ?></span
              >
            </p>
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

  <hr :class="{'loading-blur': isLoading}" />

  <div :class="{'loading-blur': isLoading}">
    <div class="settings-description">
      <h3><?php esc_html_e('Configuring Cron Jobs', 'secure-passkeys'); ?></h3>
      <?php esc_html_e('To ensure these options work as expected, it is recommended to set up a cron job on your server. A cron job is a scheduled task that automatically runs at specified intervals. Here’s how you can set up a cron job:', 'secure-passkeys'); ?>
      <br />
      <ul>
        <li>
          <?php esc_html_e('Log in to your web server control panel, such as cPanel or Plesk.', 'secure-passkeys'); ?>
        </li>
        <li>
          <?php esc_html_e('Find the option to manage cron jobs and select it.', 'secure-passkeys'); ?>
        </li>
        <li>
          <?php esc_html_e('In the “Add New Cron Job” section, specify the frequency at which you want the cron job to run, for example, every hour or every day.', 'secure-passkeys'); ?>
        </li>
        <li>
          <?php esc_html_e('In the “Command” field, enter the following command:', 'secure-passkeys'); ?>
          <pre><code>wget -q -O - <?php echo esc_html(get_site_url()); ?>/wp-cron.php?doing_wp_cron</code></pre>
        </li>
        <li><?php esc_html_e('Save the cron job.', 'secure-passkeys'); ?></li>
        <li>
          <?php esc_html_e('Add the following code to your "wp-config.php" file:', 'secure-passkeys'); ?>
          <pre><code>define('DISABLE_WP_CRON', true);</code></pre>
        </li>
      </ul>
    </div>
  </div>
</div>
