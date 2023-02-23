import {AttributeGroup} from '../../models';
import {useRouter} from '@akeneo-pim-community/shared';

const ATTRIBUTE_GROUP_MASS_DELETE_ROUTE = 'pim_structure_attributegroup_rest_mass_delete';

const useMassDeleteAttributeGroups = () => {
  const router = useRouter();

  const massDeleteAttributeGroups = async (attributeGroups: AttributeGroup[]) => {
    const response = await fetch(router.generate(ATTRIBUTE_GROUP_MASS_DELETE_ROUTE), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        codes: attributeGroups.map((attributeGroup: AttributeGroup) => attributeGroup.code),
      }),
    });

    await response.json();

    if (!response.ok) {
      console.error('Error while deleting attribute groups');
    }

    return;
  };

  return [massDeleteAttributeGroups];
};

export {useMassDeleteAttributeGroups};
