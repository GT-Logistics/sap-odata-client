<?php

namespace Gtlogistics\Sap\Odata\Enum;

enum Type: string
{
    case NULL = 'Null';

    case BINARY = 'Edm.Binary';

    case BOOLEAN = 'Edm.Boolean';

    case BYTE = 'Edm.Byte';

    case DATE_TIME = 'Edm.DateTime';

    case DECIMAL = 'Edm.Decimal';

    case DOUBLE = 'Edm.Double';

    case SINGLE = 'Edm.Single';

    case GUID = 'Edm.Guid';

    case INT_16 = 'Edm.Int16';

    case INT_32 = 'Edm.Int32';

    case INT_64 = 'Edm.Int64';

    case SIGNED_BYTE = 'Edm.SByte';

    case STRING = 'Edm.String';

    case TIME = 'Edm.Time';

    case DATE_TIME_OFFSET = 'Edm.DateTimeOffset';
}
