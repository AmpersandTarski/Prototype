export interface ListAllInterfacesInterface extends ObjectBase {
  _sortValues_: {
    Interface: string;
    Label: string;
    Is_32_public: boolean;
    Is_32_API: boolean;
  };
  Interface: ObjectBase & {
    _view_: InterfaceNameView;
  };
  Label: string;
  Is_32_public: boolean;
  Is_32_API: boolean;
  Nav_32_items: ObjectBase & {
    _view_: NavItemsNameView;
  };
  For_32_roles: ObjectBase & {
    _view_: ForRolesNameView;
  };
}

interface InterfaceNameView {
  label: string;
}

interface NavItemsNameView {
  label: string;
}

interface ForRolesNameView {
  label: string;
}

interface ObjectBase {
  _id_: string;
  _label_: string;
  _path_: string;
  _ifcs_: Array<InterfaceRefObject>;
}

interface InterfaceRefObject {
  id: string;
  label: string;
}
