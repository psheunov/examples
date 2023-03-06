Nova.booting((Vue, router, store) => {
  router.addRoutes([
    {
      name: 'edition-features',
      path: '/edition-features',
      component: require('./components/Tool'),
    },
  ])
})
