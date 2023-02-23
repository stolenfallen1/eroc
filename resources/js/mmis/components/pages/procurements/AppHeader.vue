<template>
  <v-card elevation="2">
    <v-app-bar dense>
      <template>
        <v-tabs align-with-title>
          <v-tab class="pa-1 ma-2"> For Approval Request </v-tab>
          <v-tab> Department Head </v-tab>
          <v-tab> Consultant </v-tab>
          <v-tab>  Administrator </v-tab>
        </v-tabs>
      </template>
    </v-app-bar>
  </v-card>
</template>
<script>
import AuthHelper from "@mmis/mixins/AuthHelper.vue"
export default{
  mixins:[AuthHelper],
  data(){
    return{

    }
  }
}
</script>