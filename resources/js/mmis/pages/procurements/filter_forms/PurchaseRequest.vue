<template>
  <v-row class="test-width">
    <v-col cols="6">
      <v-autocomplete v-model="filter.branch" placeholder="Branch"></v-autocomplete>
    </v-col>
    <v-col cols="6">
      <v-autocomplete v-model="filter.department" placeholder="Department"></v-autocomplete>
    </v-col>
    <v-col cols="6">
      <v-autocomplete v-model="filter.categoty" placeholder="Category"></v-autocomplete>
    </v-col>
    <v-col cols="6">
      <v-menu
        ref="menu"
        v-model="menu"
        :close-on-content-click="false"
        :return-value.sync="filter.date"
        transition="scale-transition"
        offset-y
      >
        <template v-slot:activator="{ on, attrs }">
          <v-text-field
            v-model="filter.date"
            label="Select date"
            readonly
            v-bind="attrs"
            v-on="on"
          ></v-text-field>
        </template>
        <v-date-picker
          v-model="filter.date"
          no-title
          scrollable
        >
          <v-spacer></v-spacer>
          <v-btn
            text
            color="primary"
            @click="menu = false"
          >
            Cancel
          </v-btn>
          <v-btn
            text
            color="primary"
            @click="$refs.menu.save(filter.date)"
          >
            OK
          </v-btn>
        </v-date-picker>
      </v-menu>
    </v-col>
  </v-row>
</template>
<script>
export default {
  props:{
    filter:{
      type: Object,
      default(){
        return {}
      }
    }
  },
  data(){
    return{
      menu:false
    }
  }
}
</script>
<style lang="scss" scoped>
.test-width{
  width: 500px !important;
}
</style>