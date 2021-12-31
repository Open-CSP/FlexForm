/*
 * Slim v4.15.0 - Image Cropping Made Easy
 * Copyright (c) 2017 Rik Schennink - http://slimimagecropper.com
 */
const SlimLib = require('./slim.commonjs');

// Angular core
import { ViewChild, NgModule, Component, Input, ElementRef, OnInit } from '@angular/core';

@Component({
	selector: 'slim',
	template: '<div #root><ng-content></ng-content></div>'
})

@NgModule({
	declarations: [ Slim ],
	exports: [ Slim ]
})

export class Slim {

	@ViewChild('root')
	private element: ElementRef;

	@Input()
	private options: any;

	private ngOnInit(): any {

		if (this.options.initialImage) {
			const img = document.createElement('img');
			img.setAttribute('alt', '');
			img.src = this.options.initialImage;
			this.element.nativeElement.appendChild(img);
		}

		SlimLib.create(this.element.nativeElement, this.options);
	}

};