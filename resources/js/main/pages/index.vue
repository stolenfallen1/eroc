<template>
  <v-app id="inspire">
    <v-app-bar app  flat>
      <v-app-bar-nav-icon @click="drawer = !drawer" />
      <router-link class="ml-4" to="/" tag="button">
        <v-toolbar-title class="mr-8" style="opacity: 0.87"></v-toolbar-title>
          Material Management and Inventory System</v-toolbar-title
        >
      </router-link>
      <v-spacer></v-spacer>
      <v-text-field
        flat
        solo-inverted
        hide-details
        prepend-inner-icon="mdi-magnify"
        label="Search for users, groups or settings"
        class="hidden-sm-and-down"
      />
    </v-app-bar>

    <v-main>
      <v-container class="my-5">
    <v-row>
      <v-col
        v-for="card in cards"
        :key="card.title"
        class="align-center white"
        lg="2"
        xs="2"
        xl="1"
        sm="2"
        md="1"
      >
        <v-card
          height="100px"
          width="100px"
          class="d-flex flex-column align-center"
          @click="redirect"
        >
          <v-img
            :src="card.icon"
            class="align-center"
            height="80px"
            width="80px"
            contain
          >
          </v-img>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
    </v-main>
  </v-app>
</template>

<script>
export default {
  data() {
    return {
      cards: [
        {
          icon: "https://img.icons8.com/material/96/4184f3/view-quilt.png",
          title: "Home",
          description: "See relevant insights about your organization",
          action: "/Purchase Request",
        },
        {
          icon: "https://img.icons8.com/material-sharp/96/4184f3/user.png",
          title: "Requests",
          description: "Add or manage users",
          action: "/404",
        },
        {
          icon: "https://img.icons8.com/color/96/000000/conference-foreground-selected.png",
          title: "Qoutation",
          description: "Create groups and mailing lists",
          action: "/404",
        },
        {
          icon: "https://img.icons8.com/color/96/000000/tree-structure.png",
          title: "Canvas",
          description:
            "Add, remove, rename, move or search for an organizational unit",
          action: "/404",
        },
        {
          icon: "https://img.icons8.com/color/96/000000/company.png",
          title: "Orders",
          description: "Manage and monitor buildings, rooms and resources",
          action: "/404",
        },
      ],
    };
  },
  methods: {
    redirect() {
      window.location.href = "mmis/procurements";
    },
  },
  created() {},
};
</script>
<style lang="scss" scoped>
.theme--light.v-application {
  background: #fff;
  color: rgba(0, 0, 0, 0.87);
  zoom: 0.9;
}
</style>