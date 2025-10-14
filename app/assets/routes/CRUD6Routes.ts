export default [
    {
        path: '/crud6/:model',
        meta: {
            auth: {},
            title: '',
            description: ''
        },
        children: [
            {
                path: '',
                name: 'crud6.list',
                meta: {
                    permission: {
                        slug: 'uri_crud6'
                    },
                    title: '',
                    description: ''
                },
                component: () => import('../views/PageList.vue')
            },
            {
                path: ':id',
                name: 'crud6.view',
                meta: {
                    permission: {
                        slug: 'uri_crud6'
                    },
                    title: '',
                    description: ''
                },
                component: () => import('../views/PageRow.vue')
            }
        ]
    }
]
