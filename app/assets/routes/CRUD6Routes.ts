export default [
    {
        path: 'crud6/:model',
        meta: {
            auth: {},
            title: 'CRUD6.PAGE',
            description: 'CRUD6.PAGE_DESCRIPTION'
        },
        children: [
            {
                path: '',
                name: 'admin.crud6.list',
                meta: {
                    permission: {
                        slug: 'uri_crud6'
                    }
                },
                component: () => import('../views/PageList.vue')
            },
            {
                path: ':id',
                name: 'admin.crud6',
                meta: {
                    title: 'CRUD6.PAGE',
                    description: 'CRUD6.INFO_PAGE',
                    permission: {
                        slug: 'uri_crud6'
                    }
                },
                component: () => import('../views/PageRow.vue')
            }
        ]
    }
]
