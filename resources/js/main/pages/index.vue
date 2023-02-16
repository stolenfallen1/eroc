<template>
  <v-app id="inspire">
    <v-app-bar
      app
    >
      <!-- extended -->
      <!-- <v-app-bar-nav-icon></v-app-bar-nav-icon> -->

      <!-- <v-toolbar-title>Application</v-toolbar-title> -->

      <v-spacer></v-spacer>

      <v-btn icon>
        <v-icon>mdi-dots-vertical</v-icon>
      </v-btn>
    </v-app-bar>

    <v-main>
      <v-container>
        <v-row>
          <v-col
            v-for="n in 4"
            :key="n"
            cols="6"
            @click="redirect"
          >
            <v-card height="200"></v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
  export default {
    data(){
      return {

      }
    },
    methods:{
      redirect(){
        window.location.href = 'mmis/procurements';
      }
    },
    created(){
    }
  }
</script>