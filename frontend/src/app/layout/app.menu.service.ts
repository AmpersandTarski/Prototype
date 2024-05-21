import { Injectable } from '@angular/core';
import { Subject, map, Observable } from 'rxjs';
import { Navbar, Navs, New } from '../shared/interfacing/navbar.interface';
import { MenuChangeEvent } from './api/menuchangeevent';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root',
})
export class MenuService {
  constructor(private http: HttpClient) {}

  private menuSource = new Subject<MenuChangeEvent>();
  private resetSource = new Subject();

  menuSource$ = this.menuSource.asObservable();
  resetSource$ = this.resetSource.asObservable();

  onMenuStateChange(event: MenuChangeEvent) {
    this.menuSource.next(event);
  }

  reset() {
    this.resetSource.next(true);
  }

  /* Obtain navbar navs and convert them to MenuItems */
  getMenuItems(): Observable<Array<Navs>> {
    const navbar = this.http.get<Navbar>('app/navbar');
    const navs: Observable<Array<Navs>> = navbar.pipe(map((x) => x.navs));
    return navs;
  }

  getAddButtons(): Observable<Array<New>> {
    const navbar = this.http.get<Navbar>('app/navbar');
    const addBtns: Observable<Array<New>> = navbar.pipe(map((x) => x.new));
    return addBtns;
  }

  public setSessionStorageItem(name: string, data: string) {
    sessionStorage.setItem(name, data);
  }
}
