import { Component, OnInit } from '@angular/core';
import { FormControl } from '@angular/forms';
import { map } from 'rxjs';
import { BaseAtomicComponent } from '../BaseAtomicComponent.class';

@Component({
  selector: 'app-atomic-hugealphanumeric',
  templateUrl: './atomic-hugealphanumeric.component.html',
  styleUrls: ['./atomic-hugealphanumeric.component.css'],
})
export class AtomicHugealphanumericComponent<I> extends BaseAtomicComponent<string, I> implements OnInit {
  public formControl!: FormControl<string>;

  override ngOnInit(): void {
    super.ngOnInit();

    // univalent
    if (this.isUni) {
      this.initFormControl();
    }

    // not univalent
    if (!this.isUni && this.canUpdate()) {
      this.newItemControl = new FormControl<string>('', { nonNullable: true, updateOn: 'change' });
    }
  }

  private initFormControl(): void {
    this.formControl = new FormControl<string>(this.data[0], { nonNullable: true, updateOn: `blur` });

    if (this.canUpdate()) {
      this.formControl.valueChanges
        .pipe(map((x) => (x === '' ? null : x))) // transform empty string to null value
        .subscribe((x) =>
          this.interfaceComponent
            .patch(this.resource, [
              {
                op: 'replace',
                path: this.propertyName, // FIXME: this must be relative to path of this.resource
                value: x,
              },
            ])
            .subscribe(),
        );
    }
  }
}
