<template>
  <v-card elevation="2">
    <v-app-bar dense>
      <template>
        <v-tabs v-model="setting.tab" align-with-title>
          <v-tab v-if="$store.getters.user.role.name!='comptroller'" class="pa-1 ma-2"  @click="$emit('change', 5)" > For canvas </v-tab>
          <v-tab class="pa-1 ma-2" @click="$emit('change', 6)" > For Approval </v-tab>
          <v-tab @click="$emit('change', 7)" > Approved canvas </v-tab>
        </v-tabs>
      </template>
    </v-app-bar>
  </v-card>
</template>
<script>
import PurchaseHelper from "@mmis/mixins/PurchaseHelper.vue"
export default{
  mixins:[PurchaseHelper],
  props:{
    setting:{
      type:Object,
      default: () => {}
    }
  },
  data(){
    return{

    }
  }
}
</script>