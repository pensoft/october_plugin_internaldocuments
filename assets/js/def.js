function initRightclickDeleteTippy(id, type = 'file', downloadLink = '', pUserCanDelete) {
    var html = '';
    if (type == 'image') {
		html = html + downloadLink;
    }

    if (pUserCanDelete) {
        html = html + '<a class="delete-icon" href="javascript:;" title="Delete" data-request="onDeleteFile" data-request-data="id:  ' + id + '"  data-request-confirm="Are you sure you want to delete?">Delete</a>';
    }

    var rightClickableArea = document.querySelector('#tipContainer' + id);
    if (rightClickableArea) {
        var instance = tippy(rightClickableArea, {
            placement: 'right-start',
            trigger: 'manual',
            interactive: true,
            arrow: false,
            content: html,
            allowHTML: true,
            animation: 'scale',
            theme: 'light',
        });

        rightClickableArea.addEventListener('contextmenu', (event) => {
            event.preventDefault();

            instance.setProps({
                getReferenceClientRect: () => ({
                    width: 0,
                    height: 0,
                    top: event.clientY,
                    bottom: event.clientY,
                    left: event.clientX,
                    right: event.clientX,
                }),
            });

            instance.show();
        });
    }
}

function initRightclickRenameFolderTippy(id, pUserCanDelete) {
    var rightClickableArea = document.querySelector('#tipContainer' + id);
    var html = '<a data-toggle="modal" class="modal-link" href="#contentBasicEditFolder' + id + '" style="margin-left: 0;">Rename</a>';
    if (pUserCanDelete) {
        var html = html + '<br><a class="delete-icon" href="javascript:;" title="Delete" data-request="onDeleteFolder" data-request-data="id:  ' + id + '"  data-request-confirm="Are you sure you want to delete?">Delete</a>';
    }

    if (rightClickableArea) {
        var instance = tippy(rightClickableArea, {
            placement: 'right-start',
            trigger: 'manual',
            interactive: true,
            arrow: false,
            content: html,
            allowHTML: true,
            animation: 'scale',
            theme: 'light',
        });

        rightClickableArea.addEventListener('contextmenu', (event) => {
            event.preventDefault();

            instance.setProps({
                getReferenceClientRect: () => ({
                    width: 0,
                    height: 0,
                    top: event.clientY,
                    bottom: event.clientY,
                    left: event.clientX,
                    right: event.clientX,
                }),
            });

            instance.show();
        });
    }
}

function toggleImages(id) {
    $('.all_images_container').toggleClass('changeHeight', 500);
    if ($('.show_all_btn' + id).text() == "Show all") {
        $('.show_all_btn' + id).text('Show less');
    } else if ($('.show_all_btn' + id).text() == 'Show less') {
        $('.show_all_btn' + id).text('Show all');
    }
}


function initSort(containerId, folderId, type){
	var link = document.querySelector('.accordion-toggle');
	if(link){
		link.addEventListener('click', function(event) {
			if($(this).next(".accordion-content").is(':visible')) {
				$(this).next(".accordion-content").slideUp(300);
				$(this).children(".plusminus").html('<span class="plus"></span>');
			}
		});
	}

	if(type == 'folders'){
		$( "#"+containerId ).sortable({
			revert: true,
			opacity: 0.8,
			handle: (type == 'images') ? false : ".drag-handle",
			placeholder: (type == 'images') ? false : "ui-state-highlight",
			start: function(e, ui) {
				// creates a temporary attribute on the element with the old index
				$(this).attr('data-previndex', ui.item.index());
			},
			update: function( event, ui ) {

				var id = ui.item.attr("id").replace('delete_result_','');
				var prevSiblingId = ui.item.prev();
				if(prevSiblingId.length > 0){
					prevSiblingId = ui.item.prev().attr('id').replace('delete_result_','');
					var targetNode = prevSiblingId;
					var position = 'right';
				}
				var nextSiblingId = ui.item.next();
				if(nextSiblingId.length > 0){
					nextSiblingId = ui.item.next().attr('id').replace('delete_result_','');
					var targetNode = nextSiblingId;
					var position = 'left';
				}
				$(this).removeAttr('data-previndex');

				var data = $( "#"+containerId ).sortable( "serialize", { key: "sortItem[]" } );
				$.request('onSortFolders',
					{
						data: {
							'sortOrder': data,
							'subfolderId': folderId,
							'sourceNode': id,
							'targetNode': targetNode,
							'position': position
						},
					});
			}
		});
	}else{
		$( "#"+containerId ).sortable({
			revert: true,
			opacity: 0.8,
			handle: (type == 'images') ? false : ".drag-handle",
			placeholder: (type == 'images') ? false : "ui-state-highlight",
			update: function( event, ui ) {
				var data = $( "#"+containerId ).sortable( "serialize", { key: "sortItem[]" } );
				$.request('onSortFiles',
					{
						update: {
							'@documents-files': '#sortable'
						},
						data: {
							'sortOrder': data,
							'subfolderId': folderId,
						},
					});
			}
		});
	}



	$( "#"+containerId ).disableSelection();
}


function initAccordeon(pElem, pParentId) {
	$('#tipContainer' + pParentId).parent().next().slideDown(300);
	$('#tipContainer' + pParentId).parent().children(".plusminus").html('<span class="minus"></span>');

	$('body').on('click', '.accordion-toggle', function () {
		if ($(this).next(".accordion-content").is(':visible')) {
			$(this).next(".accordion-content").slideUp(300);
			$(this).children(".plusminus").html('<span class="plus"></span>');
		} else {
			$(this).next(".accordion-content").slideDown(300);
			$(this).children(".plusminus").html('<span class="minus"></span>');
		}
	});
}
