( function ( wp ) {
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var ColorPalette = wp.components.ColorPalette;
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;
	var SelectControl = wp.components.SelectControl;
	var Spinner = wp.components.Spinner;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	var useEffect = wp.element.useEffect;
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
				default: 6,
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
			showLargeImage: {
				type: 'boolean',
				default: false,
			},
			showFeaturedBackground: {
				type: 'boolean',
				default: true,
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
			var blockProps = useBlockProps( {
				className: 'otobuton-category-post-buttons-editor',
			} );
			var categories = useSelect( function ( select ) {
				return select( 'core' ).getEntityRecords( 'taxonomy', 'category', {
					hide_empty: false,
					order: 'asc',
					orderby: 'name',
					per_page: 100,
				} );
			}, [] );
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
					el( SelectControl, {
						label: __( 'Kategori', 'otomatik-butonlar-bloku' ),
						value: attributes.categoryId,
						options: categoryOptions,
						onChange: function ( value ) {
							setAttributes( { categoryId: parseInt( value, 10 ) || 0 } );
						},
					} ),
					el( RangeControl, {
						label: __( 'Gösterilecek yazı sayısı', 'otomatik-butonlar-bloku' ),
						value: attributes.postsPerPage || 6,
						min: 1,
						max: 36,
						onChange: function ( value ) {
							setAttributes( { postsPerPage: value || 6 } );
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
