import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, ParamMap } from '@angular/router';
import { Observable, switchMap } from 'rxjs';
import { BackendService } from '../backend.service';
import { ProjectInterface } from './project.interface';
import { Router, Navigation } from '@angular/router';

@Component({
  selector: 'app-project',
  templateUrl: './project.component.html',
  styleUrls: ['./project.component.scss'],
})
export class ProjectComponent implements OnInit {
  public data$!: Observable<ProjectInterface>;
  public projectId?: string;

  constructor(private route: ActivatedRoute, private service: BackendService, private router: Router) {}

  ngOnInit(): void {
    this.data$ = this.route.paramMap.pipe(
      switchMap((params: ParamMap) => {
        this.projectId = params.get('id')!;
        if (this.projectId === null) {
        //  throw new Error('id does not exist');
          this.router.navigate(['/404'], { skipLocationChange: true });
        }
        return this.service.getProject(this.projectId);
      }),
    );
  }
}
