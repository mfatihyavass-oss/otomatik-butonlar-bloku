( function ( wp ) {
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var ColorPalette = wp.components.ColorPalette;
	var NumberControl = wp.components.NumberControl || wp.components.__experimentalNumberControl;
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;
	var SearchControl = wp.components.SearchControl;
	var SelectControl = wp.components.SelectControl;
	var Spinner = wp.components.Spinner;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	var useEffect = wp.element.useEffect;
	var useState = wp.element.useState;
	var useSelect = wp.data.useSelect;
	var ServerSideRender = wp.serverSideRender.default || wp.serverSideRender;
	var titleColorOptions = [
		{
			name: __( 'Maya koyu', 'otomatik-butonlar-bloku' ),
			slug: 'maya-dark',
			color: '#121715',
		},
		{
			name: __( 'Altın', 'otomatik-butonlar-bloku' ),
			slug: 'maya-gold',
			color: '#c8a24a',
		},
		{
			name: __( 'Yeşil', 'otomatik-butonlar-bloku' ),
			slug: 'maya-green',
			color: '#1f7a68',
		},
		{
			name: __( 'Lacivert', 'otomatik-butonlar-bloku' ),
			slug: 'maya-navy',
			color: '#1d3557',
		},
		{
			name: __( 'Bordo', 'otomatik-butonlar-bloku' ),
			slug: 'maya-burgundy',
			color: '#7a1f32',
		},
		{
			name: __( 'Siyah', 'otomatik-butonlar-bloku' ),
			slug: 'black',
			color: '#000000',
		},
	];
	var postsPerPageOptions = [
		{
			label: __( 'Sınırsız (otomatik sayfalama)', 'otomatik-butonlar-bloku' ),
			value: '0',
		},
	].concat(
		Array.from( { length: 36 }, function ( _, index ) {
			var value = String( index + 1 );

			return {
				label: value,
				value: value,
			};
		} )
	).concat( [
		{
			label: '60',
			value: '60',
		},
		{
			label: '120',
			value: '120',
		},
		{
			label: '240',
			value: '240',
		},
		{
			label: '500',
			value: '500',
		},
		{
			label: '1.000',
			value: '1000',
		},
	] );
	var sortByOptions = [
		{
			label: __( 'Yayın tarihi', 'otomatik-butonlar-bloku' ),
			value: 'date',
		},
		{
			label: __( 'Güncellenme tarihi', 'otomatik-butonlar-bloku' ),
			value: 'modified',
		},
		{
			label: __( 'Başlık', 'otomatik-butonlar-bloku' ),
			value: 'title',
		},
	];
	var sortOrderOptions = [
		{
			label: __( 'Yeniden eskiye', 'otomatik-butonlar-bloku' ),
			value: 'DESC',
		},
		{
			label: __( 'Eskiden yeniye', 'otomatik-butonlar-bloku' ),
			value: 'ASC',
		},
	];

	registerBlockType( 'otobuton/category-post-buttons', {
		apiVersion: 3,
		title: __( 'Otomatik Yazı Kutuları', 'otomatik-butonlar-bloku' ),
		category: 'widgets',
		icon: 'screenoptions',
		description: __(
			'Seçilen kategorideki en yeni yazıları otomatik olarak kutucuklarla gösterir.',
			'otomatik-butonlar-bloku'
		),
		attributes: {
			categoryId: {
				type: 'number',
				default: 0,
			},
			title: {
				type: 'string',
				default: 'Son Yazılar',
			},
			titleColor: {
				type: 'string',
				default: '#121715',
			},
			postsPerPage: {
				type: 'number',
				default: 0,
			},
			sortBy: {
				type: 'string',
				default: 'date',
			},
			sortOrder: {
				type: 'string',
				default: 'DESC',
			},
			columns: {
				type: 'number',
				default: 3,
			},
			rows: {
				type: 'number',
				default: 2,
			},
			showExcerpt: {
				type: 'boolean',
				default: false,
			},
			showDate: {
				type: 'boolean',
				default: true,
			},
			showLargeImage: {
				type: 'boolean',
				default: false,
			},
			showFeaturedBackground: {
				type: 'boolean',
				default: true,
			},
			openInNewTab: {
				type: 'boolean',
				default: false,
			},
			instanceId: {
				type: 'string',
				default: '',
			},
		},
		supports: {
			align: [ 'wide', 'full' ],
			html: false,
			spacing: {
				margin: true,
				padding: true,
			},
		},
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var categorySearchState = useState( '' );
			var categorySearch = categorySearchState[ 0 ];
			var setCategorySearch = categorySearchState[ 1 ];
			var blockProps = useBlockProps( {
				className: 'otobuton-category-post-buttons-editor',
			} );
			var categories = useSelect( function ( select ) {
				var query = {
					hide_empty: false,
					order: 'asc',
					orderby: 'name',
					per_page: 100,
				};

				if ( categorySearch ) {
					query.search = categorySearch;
				}

				return select( 'core' ).getEntityRecords( 'taxonomy', 'category', query );
			}, [ categorySearch ] );
			var selectedCategory = useSelect( function ( select ) {
				return attributes.categoryId
					? select( 'core' ).getEntityRecord( 'taxonomy', 'category', attributes.categoryId )
					: null;
			}, [ attributes.categoryId ] );
			var categoryOptions = [
				{
					label: __( 'Tüm kategoriler', 'otomatik-butonlar-bloku' ),
					value: 0,
				},
			];

			if ( categories ) {
				categoryOptions = categoryOptions.concat(
					categories.map( function ( category ) {
						return {
							label: category.name,
							value: category.id,
						};
					} )
				);
			}

			if (
				selectedCategory &&
				! categoryOptions.some( function ( option ) {
					return Number( option.value ) === selectedCategory.id;
				} )
			) {
				categoryOptions.splice( 1, 0, {
					label: selectedCategory.name,
					value: selectedCategory.id,
				} );
			}

			useEffect(
				function () {
					if ( ! attributes.instanceId ) {
						setAttributes( {
							instanceId: props.clientId.replace( /-/g, '' ).slice( 0, 12 ),
						} );
					}
				},
				[ attributes.instanceId, props.clientId ]
			);

			function renderSettings( className ) {
				return el(
					'div',
					{
						className: className,
						role: 'group',
						'aria-label': __( 'Blok ayarları', 'otomatik-butonlar-bloku' ),
					},
					! categories &&
						el(
							'div',
							{ className: 'otobuton-editor-loading' },
							el( Spinner ),
							el(
								'span',
								null,
								__( 'Kategoriler yükleniyor', 'otomatik-butonlar-bloku' )
							)
						),
					el( TextControl, {
						label: __( 'Blok başlığı', 'otomatik-butonlar-bloku' ),
						value: attributes.title || '',
						placeholder: __( 'Son Yazılar', 'otomatik-butonlar-bloku' ),
						onChange: function ( value ) {
							setAttributes( { title: value } );
						},
					} ),
					el(
						'div',
						{ className: 'otobuton-editor-color-field' },
						el(
							'span',
							{ className: 'otobuton-editor-color-field__label' },
							__( 'Başlık rengi', 'otomatik-butonlar-bloku' )
						),
						el( ColorPalette, {
							colors: titleColorOptions,
							value: attributes.titleColor || '#121715',
							clearable: false,
							onChange: function ( value ) {
								setAttributes( { titleColor: value || '#121715' } );
							},
						} )
					),
					el( SearchControl, {
						label: __( 'Kategori ara', 'otomatik-butonlar-bloku' ),
						value: categorySearch,
						placeholder: __( 'Kategori adı yazın', 'otomatik-butonlar-bloku' ),
						onChange: setCategorySearch,
					} ),
					el( SelectControl, {
						label: __( 'Kategori', 'otomatik-butonlar-bloku' ),
						value: attributes.categoryId || 0,
						options: categoryOptions,
						onChange: function ( value ) {
							setAttributes( { categoryId: parseInt( value, 10 ) || 0 } );
						},
					} ),
					el( SelectControl, {
						label: __( 'Neye göre sıralansın?', 'otomatik-butonlar-bloku' ),
						value: attributes.sortBy || 'date',
						options: sortByOptions,
						onChange: function ( value ) {
							setAttributes( { sortBy: value } );
						},
					} ),
					el( SelectControl, {
						label: __( 'Sıralama yönü', 'otomatik-butonlar-bloku' ),
						value: attributes.sortOrder || 'DESC',
						options: sortOrderOptions,
						onChange: function ( value ) {
							setAttributes( { sortOrder: value } );
						},
					} ),
					el( SelectControl, {
						label: __( 'Gösterilecek yazı sayısı', 'otomatik-butonlar-bloku' ),
						help:
							0 === Number( attributes.postsPerPage )
								? __(
									'Sınırsız seçim toplam yazı sayısını kısıtlamaz; yazılar performans için otomatik olarak sayfalara bölünür.',
										'otomatik-butonlar-bloku'
									)
								: __(
										'Bu sayı her sayfada gösterilecek yazı adedidir; fazla yazılar otomatik olarak sayfalara bölünür.',
										'otomatik-butonlar-bloku'
									),
						value: String( Number.isFinite( Number( attributes.postsPerPage ) ) ? attributes.postsPerPage : 0 ),
						options: postsPerPageOptions,
						onChange: function ( value ) {
							setAttributes( { postsPerPage: parseInt( value, 10 ) || 0 } );
						},
					} ),
					el( RangeControl, {
						label: __( 'Sütun sayısı', 'otomatik-butonlar-bloku' ),
						value: attributes.columns,
						min: 1,
						max: 6,
						onChange: function ( value ) {
							setAttributes( { columns: value || 3 } );
						},
					} ),
					el( NumberControl, {
						label: __( 'Satır sayısı', 'otomatik-butonlar-bloku' ),
						help:
							0 === Number( attributes.postsPerPage )
								? __(
										'Sınırsız modda sayfa başına yazı sayısı sütun × satır olarak hesaplanır.',
										'otomatik-butonlar-bloku'
									)
								: __(
										'Özel sayfa boyutu seçildiğinde bu ayar yalnızca kart düzenini etkiler.',
										'otomatik-butonlar-bloku'
									),
						value: attributes.rows || 2,
						min: 1,
						step: 1,
						onChange: function ( value ) {
							var rowCount = parseInt( value, 10 );

							setAttributes( { rows: Number.isFinite( rowCount ) && rowCount > 0 ? rowCount : 2 } );
						},
					} ),
					el( ToggleControl, {
						label: __( 'Tarihi göster', 'otomatik-butonlar-bloku' ),
						checked: ! ( attributes.showDate === false ),
						onChange: function ( value ) {
							setAttributes( { showDate: !! value } );
						},
					} ),
					el( ToggleControl, {
						label: __( 'Yazıyı yeni sekmede aç', 'otomatik-butonlar-bloku' ),
						help: __(
							'Etkinleştirildiğinde kart bağlantıları yeni tarayıcı sekmesinde açılır.',
							'otomatik-butonlar-bloku'
						),
						checked: !! attributes.openInNewTab,
						onChange: function ( value ) {
							setAttributes( { openInNewTab: !! value } );
						},
					} ),
					el( ToggleControl, {
						label: __( 'Yazı özeti gösterilsin mi?', 'otomatik-butonlar-bloku' ),
						help: attributes.showExcerpt
							? __(
									'Yazı başlığının altında özet metni gösterilir.',
									'otomatik-butonlar-bloku'
								)
							: __(
									'Kapalıyken kartlarda sadece tarih ve başlık görünür.',
									'otomatik-butonlar-bloku'
								),
						checked: !! attributes.showExcerpt,
						onChange: function ( value ) {
							setAttributes( { showExcerpt: !! value } );
						},
					} ),
					el( ToggleControl, {
						label: __( 'Büyük resim gösterilsin mi?', 'otomatik-butonlar-bloku' ),
						help: attributes.showLargeImage
							? __(
									'Öne çıkan görsel kartın üstünde büyük gösterilir; başlık ve özet görselin altında yer alır.',
									'otomatik-butonlar-bloku'
								)
							: __(
									'Kapalıyken kartlar daha kompakt görünür.',
									'otomatik-butonlar-bloku'
								),
						checked: !! attributes.showLargeImage,
						onChange: function ( value ) {
							setAttributes( { showLargeImage: !! value } );
						},
					} ),
					el( ToggleControl, {
						label: __(
							'Öne çıkan görseli mat arka plan yap',
							'otomatik-butonlar-bloku'
						),
						help: attributes.showLargeImage
							? __(
									'Büyük resim açıkken bu ayar kullanılmaz; görsel zaten üstte ayrı gösterilir.',
									'otomatik-butonlar-bloku'
								)
							: __(
									'Açıkken öne çıkan görsel kartın arka planında mat şekilde görünür.',
									'otomatik-butonlar-bloku'
								),
						checked: !! attributes.showFeaturedBackground,
						onChange: function ( value ) {
							setAttributes( { showFeaturedBackground: !! value } );
						},
					} )
				);
			}

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{
							title: __( 'Liste ayarları', 'otomatik-butonlar-bloku' ),
							initialOpen: true,
						},
						renderSettings( 'otobuton-editor-settings otobuton-editor-settings--panel' )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'otobuton/category-post-buttons',
						attributes: attributes,
					} ),
					props.isSelected &&
						renderSettings( 'otobuton-editor-settings otobuton-editor-settings--inline' )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
