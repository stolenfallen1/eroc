<template>
  <v-row class="test-width">
    <v-col v-if="hasFilter('branch')" cols="6">
      <v-autocomplete 
        v-model="filter.branch" 
        placeholder="Branch"
        hide-details="auto"
      ></v-autocomplete>
    </v-col>
    <v-col v-if="hasFilter('branch')" cols="6">
      <v-autocomplete 
        v-model="filter.department" 
        placeholder="Department"
        hide-details="auto"
      ></v-autocomplete>
    </v-col>
    <v-col cols="6">
      <v-autocomplete 
        v-model="filter.item_group" 
        label="Item group"
        :items="$store.getters.item_groups"
        item-text="name"
        item-value="id"
        @change="fetchCategories"
        hide-details="auto"
      ></v-autocomplete>
    </v-col>
    <v-col cols="6">
      <v-autocomplete 
        v-model="filter.category" 
        label="Category"
        :items="categories"
        item-text="name"
        item-value="id"
        hide-details="auto"
      ></v-autocomplete>
    </v-col>
    <v-col cols="6">
      <v-autocomplete 
        v-model="filter.subcategory" 
        label="Status"
        :items="subcategories"
        item-text="name"
        item-value="id"
        hide-details="auto"
      ></v-autocomplete>
    </v-col>
    <v-col cols="6">
      <v-autocomplete 
        v-model="filter.priority" 
        label="Priority"
        :items="$store.getters.priorities"
        item-text="priority_description"
        item-value="id"
        hide-details="auto"
      ></v-autocomplete>
    </v-col>
    <v-col cols="6">
      <v-menu
        v-model="requested_date"
        :close-on-content-click="false"
        transition="scale-transition"
        offset-y
      >
        <template v-slot:activator="{ on, attrs }">
          <v-text-field
            clearable
            v-model="filter.requested_date"
            label="Requested date"
            hide-details="auto"
            @click:clear="filter.requested_date = null"
            readonly
            v-bind="attrs"
            v-on="on"
          ></v-text-field>
        </template>
        <v-date-picker
          v-model="filter.requested_date"
          @change="requested_date = false"
          no-title
          scrollable
        >
        </v-date-picker>
      </v-menu>
    </v-col>
    <v-col cols="6">
      <v-menu
        v-model="required_date"
        :close-on-content-click="false"
        transition="scale-transition"
        offset-y
      >
        <template v-slot:activator="{ on, attrs }">
          <v-text-field
            clearable
            v-model="filter.required_date"
            label="Required date"
            hide-details="auto"
             @click:clear="filter.required_date = null"
            readonly
            v-bind="attrs"
            v-on="on"
          ></v-text-field>
        </template>
        <v-date-picker
          v-model="filter.required_date"
          @change="required_date = false"
          no-title
          scrollable
        >
        </v-date-picker>
      </v-menu>
    </v-col>
  </v-row>
</template>
<script>
import PurchaseHelper from '@mmis/mixins/PurchaseHelper.vue'
import { apiGetAllCategories, apiGetAllSubCategories } from '@global/api/categories'
export default {
  mixins:[PurchaseHelper],
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
      required_date:false,
      requested_date:false,
      categories:[],
      subcategories:[],
    }
  },
  methods:{
    async fetchCategories(){
      let params = ''
      if(this.filter.item_group){
        this.filter.category = null
        params += 'invgroup_id='+this.filter.item_group
      }
      let res = await apiGetAllCategories(params)
      if(res.status == 200){
        this.categories = res.data.categories
      }
    },
    async fetchSubCategories(){
      let params = ''
      if(this.filter.category){
        this.filter.subcategory = null
        params += 'category_id='+this.filter.category
      }
      let res = await apiGetAllSubCategories(params)
      if(res.status == 200){
        this.subcategories = res.data.subcategories
      }
    }
  },
  watch:{
    filter:{
      handler(){
        this.fetchCategories()
        this.fetchSubCategories()
      },
      deep:true, immediate: true
    }
  },
  // created(){
  //   this.fetchCategories()
  //   this.fetchSubCategories()
  // }
}
</script>
<style lang="scss" scoped>
.test-width{
  width: 500px !important;
}
</style>