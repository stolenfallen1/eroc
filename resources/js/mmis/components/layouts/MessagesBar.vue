<template>
    <div>
        <v-menu
        offset-y
        left
        nudge-bottom="14"
        min-width="230"
        content-class="user-profile-menu-content"
      >
        <template v-slot:activator="{ on, attrs }">
        <v-btn icon v-bind="attrs" v-on="on">
          <v-icon>mdi-chat</v-icon>
        </v-btn>
        </template>
        <v-list>
          <div class="pb-1 pt-1">
            <v-badge
              bottom
              color="success"
              overlap
              offset-x="12"
              offset-y="12"
              class="ms-4"
              dot
            >
              <v-avatar size="40px">
                <img
                  src="https://cdn.vuetifyjs.com/images/john.jpg"
                  alt="John"
                />
              </v-avatar>
            </v-badge>
            <div
              class="d-inline-flex flex-column justify-center ms-3"
              style="vertical-align: middle"
            >
              <small class="text--disabled text-capitalize"></small>
            </div>
          </div>
          <v-divider></v-divider>
          <!-- Profile -->
          <v-list-item link>
            <v-list-item-icon class="me-2"> </v-list-item-icon>
            <v-list-item-content>
              <v-list-item-title>My Account</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-divider class="my-2"></v-divider>
          <!-- Logout -->
          <v-list-item link>
            <v-list-item-icon class="me-2"> </v-list-item-icon>
            <v-list-item-content>
              <v-list-item-title> Logout</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list>
      </v-menu>
    </div>
  </template>
  <script>
  export default {
    data: () => ({
      fav: true,
      menu: false,
      message: false,
      hints: true,
    }),
  };
</script>