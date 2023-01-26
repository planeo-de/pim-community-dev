import {useState} from 'react';
import {AttributeGroup} from '../../models';

const useRefreshAttributeGroups = (): {
  refreshSelection: (code: string) => void;
  refreshOrder: (attributeGroups: AttributeGroup[]) => void;
  itemSelected: boolean;
} => {
  const [attributeGroups, setAttributeGroups] = useState<AttributeGroup[]>([]);
  const [itemSelected, setItemSelected] = useState(false);

  const refresh = (AttributeGroups: AttributeGroup[]) => {
    setItemSelected(
      AttributeGroups.map(attributeGroup => {
        return attributeGroup.selected;
      }).includes(true)
    );
    setAttributeGroups(AttributeGroups);
  };

  /**
  const saveOrder = useCallback(async () => {
    let order: {[code: string]: number} = {};

    groups.forEach(attributeGroup => {
      order[attributeGroup.code] = attributeGroup.sort_order;
    });

    await saveAttributeGroupsOrder(order);
  }, [groups]);
  **/

  const refreshSelection = (code: string) => {
    const reorderedGroups = attributeGroups.map(attributeGroup => {
      if (attributeGroup.code === code) {
        return {
          ...attributeGroup,
          selected: !attributeGroup.selected,
        };
      }

      return attributeGroup;
    });

    refresh(reorderedGroups);
  };

  const refreshOrder = (attributeGroups: AttributeGroup[]) => {
    const reorderedGroups = attributeGroups.map((item, index) => {
      return {
        ...item,
        sort_order: index,
      };
    });

    refresh(reorderedGroups);
  };

  return {
    refreshSelection,
    refreshOrder,
    itemSelected,
  };
};

export {useRefreshAttributeGroups};
