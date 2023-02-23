<script>
export default {
  data(){
    return{
      pr_approver_roles:[
        'department head',
        'administrator'
      ],

    }
  },
  methods:{
    can(val){
      return this.$store.getters.user_permissions.some(permission => permission.key == val)
    },
    isAuthorize(tab){
      if(tab == 'pr') return this.pr_approver_roles.some(role => role == this.$store.getters.user.role.name)
    }
  }
}
</script>