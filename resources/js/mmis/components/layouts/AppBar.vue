<template>
  <v-app-bar app  fixed flat>
    <v-app-bar-nav-icon @click="$emit('toggle')"></v-app-bar-nav-icon>
    <!-- <v-toolbar-title>Collapsing Bar</v-toolbar-title> -->
    <v-spacer></v-spacer>
  </v-app-bar>
</template>
<script>
export default {
  props: {},
  data() {
    return {};
  },
  watch: {},
};
</script>