import React, {createContext, FC} from 'react';
import {AttributeGroup} from '../../models';
import {useAttributeGroups} from '../../hooks';

type AttributeGroups = {
  attributeGroups: AttributeGroup[];
  isPending: boolean;
};

const AttributeGroupsIndexContext = createContext<AttributeGroups>({
  attributeGroups: [],
  isPending: true,
});

const AttributeGroupsIndexProvider: FC = ({children}) => {
  const state = useAttributeGroups();
  return <AttributeGroupsIndexContext.Provider value={state}>{children}</AttributeGroupsIndexContext.Provider>;
};

export {AttributeGroupsIndexProvider, AttributeGroups, AttributeGroupsIndexContext};
