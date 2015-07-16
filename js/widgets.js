/**
 * @package Content Aware Sidebars
 * @author Joachim Jensen <jv@intox.dk>
 */

(function($) {

	var cas_admin = {

		$sidebarContainer: $(".widget-liquid-right"),
		$widgetContainer: $('#available-widgets'),
		$sidebars:null,
		$widgets:null,

		/**
		 * Initiate
		 *
		 * @since  3.0
		 * @return {void}
		 */
		init: function() {

			this.$sidebars = $("div[id^='ca-sidebar']", this.$sidebarContainer);
			this.$widgets = $(".widget",this.$widgetContainer).get().reverse();

			this.addSidebarEditLink();
			this.addSidebarToolbar();
			this.addWidgetSearch();

		},
		/**
		 * Add search input for widgets
		 *
		 * @since 3.0
		 */
		addWidgetSearch: function() {
			$(".sidebar-description",this.$widgetContainer).prepend('<input type="search" class="js-cas-widget-filter" placeholder="'+CASAdmin.filterWidgets+'...">');
			this.searchWidgetListener();
		},
		/**
		 * Listen to widget filter
		 *
		 * @since  3.0
		 * @return {void}
		 */
		searchWidgetListener: function() {
			var that = this,
				filterTimer,
				cachedFilter = "";
			this.$widgetContainer.on('keyup', '.js-cas-widget-filter',function(e) {
				var filter = $(this).val();
				if(filter != cachedFilter) {
					cachedFilter = filter;
					if( filterTimer ) {
						clearTimeout(filterTimer);
					}
					filterTimer = setTimeout(function(){
						$(that.$widgets).each(function(key,widget) {
							var $widget = $(widget);
							if ($widget.find("h4").first().text().search(new RegExp(filter, "i")) < 0) {
								$widget.fadeOut();
							} else {
								//CSS dependent on order, so move to top
								$widget.prependTo($widget.parent());
								$widget.fadeIn().css("display","");
							}
						});
					}, 250);
				}
			});
		},
		/**
		 * Add toolbar for sidebars
		 *
		 * @since 3.0
		 */
		addSidebarToolbar: function() {

			var box = '<div class="wp-filter" style="margin: 10px 0px; vertical-align: middle;">'+
			'<a href="post-new.php?post_type=sidebar" class="button button-primary" style="margin: 12px 0 11px">'+CASAdmin.addNew+'</a>'+
			'<input type="search" class="js-cas-filter" placeholder="'+CASAdmin.filterSidebars+'..." style="margin: 12px 0 11px;float: right;">'+
			'</div>';

			this.$sidebarContainer.prepend(box);
			this.searchSidebarListener();

		},
		/**
		 * Listen to sidebar filter
		 *
		 * @since  3.0
		 * @return {void}
		 */
		searchSidebarListener: function() {
			var that = this,
				filterTimer,
				cachedFilter = "";
			this.$sidebarContainer.on('keyup', '.js-cas-filter',function(e) {
				var filter = $(this).val();
				if(filter != cachedFilter) {
					cachedFilter = filter;
					if( filterTimer ) {
						clearTimeout(filterTimer);
					}
					filterTimer = setTimeout(function(){
						$(".widgets-holder-wrap",that.$sidebarContainer).each(function(key,sidebar) {
							var $sidebar = $(sidebar);
							if ($sidebar.find("h3").first().text().search(new RegExp(filter, "i")) < 0) {
								$sidebar.fadeOut();
							} else {
								$sidebar.fadeIn();
							}
						});
					}, 250);
				}
			});
		},
		/**
		 * Add better management for
		 * each sidebar
		 *
		 * @since 3.0
		 */
		addSidebarEditLink: function() {

			this.$sidebars.each( function(e) {
				$this = $(this);
				var id = $this.attr('id').replace('ca-sidebar-','');
				var $sidebar = $this.closest('.widgets-holder-wrap');

				$sidebar.addClass('content-aware-sidebar');

				$this.find('.sidebar-description').append('<div class="cas-settings"><a title="'+CASAdmin.edit+'" class="cas-sidebar-link" href="post.php?post='+id+'&action=edit"><i class="dashicons dashicons-admin-generic"></i> '+CASAdmin.edit+'</a></div>');

			});
		}

	};

	$(document).ready(function(){ cas_admin.init(); });

})(jQuery);
