const purchase = {
  path: `/mmis/procurements/purchase-request`,
  name: `purchase-request`,
  component: () => import("../pages/procurements/purchase_request/index.vue")
};

const component = [purchase];
export default component;