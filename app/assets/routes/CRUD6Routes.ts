export default [
    {
        path: '/crud6/:model',
        meta: {
            auth: {},
            description: 'CRUD6.PAGE_DESCRIPTION'
        },
        component: () => import('../views/PageList.vue'),
        beforeEnter: (to, from, next) => {
            // If ID param exists, skip to child route - prevents PageList from mounting on detail routes
            if (to.params.id) {
                next()
            } else {
                next()
            }
        },
        children: [
            {
                path: ':id',
                name: 'crud6.view',
                meta: {
                    title: 'CRUD6.PAGE',
                    description: 'CRUD6.INFO_PAGE',
                    permission: {
                        slug: 'uri_crud6'
                    }
                },
                component: () => import('../views/PageDynamic.vue')
            }
        ]
    }
]
