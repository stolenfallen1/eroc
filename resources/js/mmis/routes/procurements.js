const procurement = {
  path: `/mmis/procurements`,
  name: `procurements`,
  // redirect: {
  //   name: 'purchase-request'
  // }
  component: () => import("../pages/procurements/dashboard/index.vue")
};
const purchase = {
  path: `/mmis/procurements/purchase-request`,
  name: `purchase-request`,
  component: () => import("../pages/procurements/purchase_request/index.vue")
};

const component = [purchase, procurement];
export default component;