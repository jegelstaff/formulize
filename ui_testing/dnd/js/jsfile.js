onload = function () {

    // TreeView data
    var items = [
        { header: 'Electronics', img: 'resources/electronics.png', items: [
            { header: 'Trimmers/Shavers' },
            { header: 'Tablets' },
            { header: 'Phones', img: 'resources/phones.png', items: [
                { header: 'Apple' },
                { header: 'Motorola' },
                { header: 'Nokia' },
                { header: 'Samsung' }
            ]},
            { header: 'Speakers' },
            { header: 'Monitors' }
        ]},
        { header: 'Toys', img: 'resources/toys.png', items: [
            { header: 'Shopkins' },
        ]},
        { header: 'Home', img: 'resources/home.png', items: [
            { header: 'Coffeee Maker' },
        ]}
    ];

    // create and bind the TreeView
    var tv = new wijmo.nav.TreeView('#tv', {
        displayMemberPath: 'header',
        childItemsPath: 'items',
        itemsSource: items
    });

    // handle collapse/expand buttons
    document.getElementById('btnCollapse').addEventListener('click', function () {
        tv.collapseToLevel(0);
    });
    document.getElementById('btnExpand').addEventListener('click', function () {
        tv.collapseToLevel(1000);
    });

    // handle checkboxes
    document.getElementById('chkAutoCollapse').addEventListener('change', function (e) {
        tv.autoCollapse = e.target.checked;
    });
    document.getElementById('chkIsAnimated').addEventListener('change', function (e) {
        tv.isAnimated = e.target.checked;
    });
    document.getElementById('chkExpandOnClick').addEventListener('change', function (e) {
        tv.expandOnClick = e.target.checked;
    });
}
                   