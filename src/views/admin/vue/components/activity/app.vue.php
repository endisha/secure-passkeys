<div v-cloak>
  <h1>
    <?php esc_html_e('Activity Log', 'secure-passkeys'); ?>
  </h1>
  <p>
    <?php esc_html_e('View a detailed log of passkeys created by users, including timestamps and relevant activity history for better security monitoring.', 'secure-passkeys'); ?>
  </p>

  <transition name="fade">
    <div class="error" v-if="missingNonce || errorMessage != ''">
      <p>
        {{ errorMessage }}
      </p>
    </div>
  </transition>

  <transition name="fade">
    <div class="updated" v-if="successMessage != ''">
      <p>
        {{ successMessage }}
      </p>
    </div>
  </transition>

  <hr />

  <div v-if="!missingNonce">
    <div class="tablenav">

      <div class="alignleft removealignleft">
        <label for="user_id" class="inline-label">
          <?php esc_html_e('User', 'secure-passkeys'); ?>
        </label>

        <input
          type="text"
          id="user_id"
          style="width: 250px;"
          class="form-control user_id"
          placeholder="<?php echo esc_attr__('User ID, Name, or Email', 'secure-passkeys'); ?>"
          />
      </div>

      <div class="alignleft">
        <label for="log_type" class="inline-label">
          <?php esc_html_e('Action', 'secure-passkeys'); ?>
        </label>

        <select
          v-model="filters.log_type"
          class="form-control"
          id="log_type"
          @keyup.enter="filterList"
        >
          <option value="">
            <?php esc_html_e('Any', 'secure-passkeys'); ?>
          </option>
          <option value="login">
            <?php esc_html_e('Login', 'secure-passkeys'); ?>
          </option>
          <option value="register">
            <?php esc_html_e('Register', 'secure-passkeys'); ?>
          </option>
          <option value="delete">
            <?php esc_html_e('Delete', 'secure-passkeys'); ?>
          </option>
          <option value="remove">
            <?php esc_html_e('Remove', 'secure-passkeys'); ?>
          </option>
          <option value="activate">
            <?php esc_html_e('Activate', 'secure-passkeys'); ?>
          </option>
          <option value="deactivate">
            <?php esc_html_e('Deactivate', 'secure-passkeys'); ?>
          </option>
        </select>
      </div>

      <div class="alignleft">
        <label for="action_by" class="inline-label">
          <?php esc_html_e('Action By', 'secure-passkeys'); ?>
        </label>

        <select
          v-model="filters.action_by"
          class="form-control"
          id="action_by"
          @keyup.enter="filterList"
        >
          <option value="">
            <?php esc_html_e('Any', 'secure-passkeys'); ?>
          </option>
          <option value="user">
            <?php esc_html_e('User', 'secure-passkeys'); ?>
          </option>
          <option value="admin">
            <?php esc_html_e('Admin', 'secure-passkeys'); ?>
          </option>
        </select>
      </div>

      <div class="alignleft">
        <label for="ip_address" class="inline-label">
          <?php esc_html_e('IP Address', 'secure-passkeys'); ?>
        </label>

        <input
          type="text"
          v-model="filters.ip_address"
          id="ip_address"
          class="form-control"
          @keyup.enter="filterList()"
        />
      </div>

      <div class="alignleft">
        <label for="created_at" class="inline-label">
          <?php esc_html_e('Created Date', 'secure-passkeys'); ?>
        </label>

        <input
          type="text"
          v-model="filters.created_at"
          id="created_at"
          class="form-control created_at"
          @keyup.enter="filterList()"
        />
      </div>

      <div class="alignleft">
        <button class="button" type="button" @click="filterList()">
          <span
            :class="{'spinner is-active spin spinner-button': isLoading}"
          ></span>
          <?php esc_html_e('Filter', 'secure-passkeys'); ?>
        </button>
        <button
          class="button button-reset"
          type="button"
          @click="filterReset()"
          v-show="hasFilters"
        >
          <?php esc_html_e('Reset', 'secure-passkeys'); ?>
        </button>
      </div>
    </div>

    <hr />

    <div>
      <b class="table-count"
        ><?php esc_html_e('Count', 'secure-passkeys'); ?>:
        {{ count < 0 ? "" : count }}
        <i v-if="count < 0" class="spinner is-active spin spinner-button"></i
      ></b>
    </div>

    <table
      class="wp-list-table passkeys widefat fixed striped table-view-list"
      width="100%"
    >
      <thead>
        <tr>
          <th class="manage-column column-id">
            <?php esc_html_e('#', 'secure-passkeys'); ?>
          </th>
          <th class="manage-column column-user">
            <?php esc_html_e('User', 'secure-passkeys'); ?>
          </th>
          <th class="manage-column column-description">
            <?php esc_html_e('Description', 'secure-passkeys'); ?>
          </th>
          <th class="manage-column column-action">
            <?php esc_html_e('Action', 'secure-passkeys'); ?>
          </th>
          <th class="manage-column column-ip_address">
            <?php esc_html_e('IP Address', 'secure-passkeys'); ?>
          </th>
          <th class="manage-column column-created_at">
            <?php esc_html_e('Created Date', 'secure-passkeys'); ?>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in list" :key="item.id" v-if="!isLoading">
          <td data-label="<?php esc_html_e('#', 'secure-passkeys'); ?>">{{ item.id }}</td>
          <td data-label="<?php esc_html_e('User', 'secure-passkeys'); ?>">
          <a
              :href="item.user.page_url"
              target="_blank"
              v-if="item.user?.name"
            >
              {{ item.user.name }}
            </a>
            <template v-else>
              <?php esc_html_e('User ID', 'secure-passkeys'); ?>:
              {{ item.user_id }}
            </template>
          </td>
          <td data-label="<?php esc_html_e('Description', 'secure-passkeys'); ?>">
            {{ item.description }}
            <template v-if="item.admin">
              -
              <a :href="item.admin?.page_url" target="_blank">{{
                item.admin?.name
              }}</a>
            </template>
          </td>
          <td data-label="<?php esc_html_e('Action', 'secure-passkeys'); ?>">
            <span :class="statusClass(item)">
              {{ item.localized_log_type }}
            </span>
          </td>
          <td data-label="<?php esc_html_e('IP Address', 'secure-passkeys'); ?>">{{ item.ip_address }}</td>
          <td data-label="<?php esc_html_e('Created Date', 'secure-passkeys'); ?>">{{ item.created_at }}</td>
        </tr>
        <tr v-if="list.length == 0 && !isLoading">
          <td colspan="6" class="center no-label">
            <?php esc_html_e('No records found.', 'secure-passkeys'); ?>
          </td>
        </tr>
        <tr v-if="isLoading">
          <td colspan="6" class="center no-label" style="text-align: center">
            <i
              class="spinner is-active spin"
              style="text-align: center; margin: auto"
            ></i>
          </td>
        </tr>
      </tbody>
    </table>

    <paginate
      :count="count"
      :pages="pages"
      :current="current"
      @navigate="loadPage"
    ></paginate>
  </div>
</div>
