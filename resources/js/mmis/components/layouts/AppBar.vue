<template>
  <v-app-bar app>
    <v-app-bar-nav-icon @click="$emit('toggle', !drawer)"></v-app-bar-nav-icon>
    <!-- <v-toolbar-title>Collapsing Bar</v-toolbar-title> -->
    <v-spacer></v-spacer>
    <div class="text-center">
      <v-menu offset-y>
        <template v-slot:activator="{ on, attrs }">
          <!-- <v-btn color="primary" dark  icon>
              Dropdown
            </v-btn> -->
          <v-avatar v-bind="attrs" v-on="on">
            <img src="https://cdn.vuetifyjs.com/images/john.jpg" alt="John" />
          </v-avatar>
        </template>
        <v-list>
          <v-list-item>
            <v-list-item-title>Profile</v-list-item-title>
          </v-list-item>
          <v-list-item>
            <v-list-item-title>Logout</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
    </div>
  </v-app-bar>
</template>
<script>
export default {
  props:{
    drawer:{
      type:Boolean,
      default(){
        return true
      }
    }
  },
  data() {
    return{
    }
  },
  watch:{
    
  }

};
</script>