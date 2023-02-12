<template>
  <div>
    procurement dashboard
    <right-side-bar :hide="['actions']"/>
  </div>
</template>
<script>
import RightSideBar from "@mmis/components/pages/RightSideBar.vue";
export default {
  components:{
    RightSideBar
  },
  data(){
    return{

    }
  },
}
</script>