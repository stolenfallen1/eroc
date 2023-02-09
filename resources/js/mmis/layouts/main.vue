<template>
  <!-- App.vue -->
  <v-app>
    <!-- <v-navigation-drawer app>
    </v-navigation-drawer> -->
    <side-bar :drawer="drawer" />

    <app-bar :drawer="drawer" @toggle="toggleSide"/>

    <!-- Sizes your content based upon application components -->
    <v-main>
      <!-- Provides the application the proper gutter -->
      <v-container fluid>
        <!-- If using vue-router -->
        <router-view></router-view>
      </v-container>
    </v-main>

    <v-footer app>
      <!-- -->
    </v-footer>
  </v-app>
</template>
<script>
import SideBar from "../components/layouts/SideBar.vue"
import AppBar from "../components/layouts/AppBar.vue"
export default {
  components:{
    SideBar,
    AppBar
  },
  data(){
    return{
      drawer:true
    }
  },
  methods:{
    toggleSide(val){
      console.log(val)
      this.drawer = val
    }
  }
  
}
</script>