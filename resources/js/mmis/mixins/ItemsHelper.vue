<script>
export default {
  methods: {
    checkPRPayload(payload) {
      let errors = [];
      if (this.$store.getters.prsn_settings.isActive) {
        if (!payload.pr_Document_Prefix)
          errors.push({ message: "Prefix Purchase number is required" });
        if (!payload.pr_Document_Number)
          errors.push({ message: "Purchase number is required" });
        if (!payload.pr_Document_Suffix)
          errors.push({ message: "Suffix Purchase number is required" });
      }
      if (!payload.pr_Justication)
        errors.push({ message: "Justication is required" });
      if (!payload.pr_Transaction_Date_Required)
        errors.push({ message: "Required date is required" });
      if (!payload.pr_Priority_Id)
        errors.push({ message: "Priority is required" });
      if (!payload.invgroup_id)
        errors.push({ message: "Item group is required" });
      if (!payload.item_Category_Id)
        errors.push({ message: "Category is required" });
      if (!payload.item_SubCategory_Id)
        errors.push({ message: "Subcategory is required" });
      if (!payload.item_Category_Id)
        errors.push({ message: "Category is required" });
      if (this.payload.items.length < 1)
        errors.push({ message: "Item is required" });
      payload.items.map((item) => {
        if (!item.item_Request_Qty || item.item_Request_Qty == 0) {
          errors.push({ message: "Quantity is required" });
        }
        if (
          !item.item_Request_UnitofMeasurement_Id ||
          item.item_Request_UnitofMeasurement_Id == 0
        ) {
          errors.push({ message: "Unit of measurement is required" });
        }
      });

      return errors;
    },

    checkPRStatus(payload) {
      if (payload.status) {
        if (payload.status.Status_description.toLowerCase() == "pending") {
          return false;
        }
        return true;
      }
    },

    checkApproveItems(payload) {
      let flag = false;
      if (payload.items.some((item) => item.isapproved == true)) {
        flag = true;
      }
      payload.isapproved = flag;
      return flag;
    },

    hasActions(setting) {
      console.log(setting, "has actions");
      if (setting.param_tab > 1) {
        return true;
      } else {
        return false;
      }
    },

    hasTab(tab) {
      if (this.$store.getters.user.role.name == "administrator") {
        if (tab == "consultant") {
          return false;
        }
      } else if (this.$store.getters.user.role.name == "consultant") {
        if (tab == "administrator") {
          return false;
        }
      }
      return true;
    },

    hasFilter(field) {
      if (
        this.$store.getters.user.role.name == "staff" ||
        this.$store.getters.user.role.name == "department head"
      ) {
        if (field == "branch" || field == "department") {
          return false;
        }
      }
      return true;
    },
  },
};
</script>