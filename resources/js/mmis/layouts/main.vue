<template>
  <!-- App.vue -->
  <v-app>
    <!-- <v-navigation-drawer app>
    </v-navigation-drawer> -->
    <side-bar :drawer="isdrawer" />

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
export default {
  components:{
    SideBar,
  },
  data(){
    return{
      drawer:false,
      isdrawer:true
    }
  },
  methods:{
  },
  watch:{

  }
  
}
</script>