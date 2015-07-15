SiteFactory.FineUploaderDefaultSettings = function() {
	this.element = null;
	this.formElement = null;
	this.fieldName = '';
	this.template = 'qq-template-validation';
	this.deleteFile = {
		enabled: true
	};
	this.request = {
		endpoint:       TYPO3.settings.ajaxUrls['ajaxDispatcher'],
		paramsInBody:   false,
		params: {
			ajaxID:		'ajaxDispatcher',
			request: {
				function: 'Romm\\SiteFactory\\Utility\\FileUtility->ajaxMoveUploadedFileToSiteFactoryFolder'
			}
		}
	};
	this.thumbnails = {
		placeholders: {
			waitingPath: '',
			notAvailablePath: ''
		}
	};
	this.validation = {
		allowedExtensions: ['jpeg', 'jpg', 'gif', 'png', 'bmp'/* @todo: delete BMP */],
		itemLimit: 5,
		sizeLimit: 409600000 // 400 kB = 400 * 1024 bytes
	};
	this.classes = {
		fail:		'alert alert-danger counter-errors',
		success:	'alert alert-info'
	};
	this.messages =  {};
	this.callbacks = {
		onComplete: function(id, name, response) {
			// Changing the value of the form element to the path of the file.
			var formElement = window[this._options.formId];
			var fieldName = this._options.fieldName;
			var fieldElement = formElement.getFieldByName(fieldName);
			fieldElement.input.val('new:' + response['tmpFilePath']);
		}
	};
};